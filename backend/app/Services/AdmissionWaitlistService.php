<?php

namespace App\Services;

use App\Models\AdmissionWaitlist;
use App\Models\AppConfig;
use App\Models\Course;
use App\Models\ProgrammeBatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AdmissionWaitlistService
{
    /**
     * Add a user to the waitlist for a specific course.
     */
    public function addToWaitlist(User $user, Course $course, ?int $programmeBatchId = null): AdmissionWaitlist
    {
        // Check if already on waitlist
        $existing = AdmissionWaitlist::where('user_id', $user->userId)
            ->where('course_id', $course->id)
            ->whereIn('status', ['pending', 'notified'])
            ->first();

        if ($existing) {
            throw new \Exception('You are already on the waitlist for this course.');
        }

        // Check if user already has a booking or admission for this course
        $hasBooking = \App\Models\Booking::where('user_id', $user->userId)
            ->where('course_id', $course->id)
            ->exists();

        if ($hasBooking) {
            throw new \Exception('You already have a booking for this course.');
        }

        return AdmissionWaitlist::create([
            'user_id' => $user->userId,
            'course_id' => $course->id,
            'programme_batch_id' => $programmeBatchId,
            'status' => 'pending',
        ]);
    }

    /**
     * Remove a user from the waitlist.
     */
    public function removeFromWaitlist(User $user, int $courseId): bool
    {
        $waitlist = AdmissionWaitlist::where('user_id', $user->userId)
            ->where('course_id', $courseId)
            ->whereIn('status', ['pending', 'notified'])
            ->first();

        if (! $waitlist) {
            return false;
        }

        $waitlist->markAsRemoved();

        return true;
    }

    /**
     * Check if a user is on the waitlist for a course.
     */
    public function isOnWaitlist(User $user, int $courseId): bool
    {
        return AdmissionWaitlist::where('user_id', $user->userId)
            ->where('course_id', $courseId)
            ->whereIn('status', ['pending', 'notified'])
            ->exists();
    }

    /**
     * Get all waitlist entries for a user.
     */
    public function getUserWaitlist(User $user): \Illuminate\Support\Collection
    {
        return AdmissionWaitlist::with(['course.programme', 'course.centre', 'programmeBatch'])
            ->where('user_id', $user->userId)
            ->whereIn('status', ['pending', 'notified', 'converted'])
            ->oldestFirst()
            ->get();
    }

    /**
     * Get waitlist count for a course.
     */
    public function getWaitlistCount(int $courseId): int
    {
        return AdmissionWaitlist::where('course_id', $courseId)
            ->whereIn('status', ['pending', 'notified'])
            ->count();
    }

    /**
     * Notify waitlisted users for a programme batch.
     * This is called by the NotifyWaitlistedUsers listener.
     */
    public function notifyWaitlistedUsers(ProgrammeBatch $programmeBatch): void
    {
        // Guard: skip if admission_waitlist table doesn't exist
        if (! Schema::hasTable('admission_waitlist')) {
            Log::warning('Skipping waitlist notification: admission_waitlist table does not exist', [
                'batch_id' => $programmeBatch->id,
            ]);

            return;
        }

        $limit = (int) AppConfig::getValue('WAITLIST_NOTIFY_LIMIT', 5);

        $programme = $programmeBatch->programme;
        if (! $programme) {
            return;
        }

        // Find waitlist entries — using the course linked to the programme
        $waitlistEntries = DB::table('admission_waitlist')
            ->join('courses', 'admission_waitlist.course_id', '=', 'courses.id')
            ->where('courses.programme_id', $programme->id)
            ->where('courses.centre_id', $programmeBatch->centre_id)
            ->where('admission_waitlist.status', 'pending')
            ->orderBy('admission_waitlist.created_at', 'asc')
            ->limit($limit)
            ->select('admission_waitlist.user_id', 'admission_waitlist.course_id', 'admission_waitlist.id as waitlist_id')
            ->get();

        if ($waitlistEntries->isEmpty()) {
            return;
        }

        // Batch-load all users in a single query (avoid N+1)
        $userIds = $waitlistEntries->pluck('user_id')->unique()->toArray();
        $usersById = User::whereIn('userId', $userIds)->get()->keyBy('userId');

        foreach ($waitlistEntries as $entry) {
            $user = $usersById->get($entry->user_id);
            if (! $user || ! $user->email) {
                continue;
            }

            try {
                $user->notify(new \App\Notifications\WaitlistSlotAvailableNotification(
                    $programmeBatch,
                    $entry->course_id
                ));

                // Update waitlist entry
                AdmissionWaitlist::where('id', $entry->waitlist_id)
                    ->update([
                        'status' => 'notified',
                        'notified_at' => now(),
                    ]);

                Log::info('Waitlist notification sent', [
                    'user_id' => $user->userId,
                    'batch_id' => $programmeBatch->id,
                    'waitlist_id' => $entry->waitlist_id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send waitlist notification', [
                    'user_id' => $user->userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Convert a waitlist entry to a booking.
     */
    public function convertWaitlistEntry(AdmissionWaitlist $waitlist, BookingService $bookingService): \App\Models\Booking
    {
        if ($waitlist->status !== 'notified') {
            throw new \Exception('Waitlist entry must be notified before conversion.');
        }

        $user = User::where('userId', $waitlist->user_id)->first();
        $course = Course::find($waitlist->course_id);
        $batch = ProgrammeBatch::find($waitlist->programme_batch_id);

        if (! $user || ! $course || ! $batch) {
            throw new \Exception('Invalid waitlist entry: missing related records.');
        }

        // Find a master session for this course type
        $session = \App\Models\MasterSession::where('course_type', $course->programme->courseType())
            ->where('status', true)
            ->first();

        if (! $session) {
            throw new \Exception('No available session for this course type.');
        }

        // Create booking
        $booking = $bookingService->book($user, $course, $batch, $session);

        // Mark waitlist as converted
        $waitlist->markAsConverted();

        return $booking;
    }
}
