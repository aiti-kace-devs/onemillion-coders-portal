<?php

namespace App\Services;

use App\Events\AdmissionSlotFreed;
use App\Models\Booking;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\MasterSession;
use App\Models\ProgrammeBatch;
use App\Models\User;
use App\Models\UserAdmission;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * Book a user into a programme batch for a specific course session.
     *
     * Enforces programme-batch capacity (available_slots) and per-session capacity
     * (confirmed bookings < centre.{short|long}_slots_per_day).
     *
     * @throws Exception when either capacity is exhausted or the session is incompatible.
     */
    public function book(User $user, Course $course, ProgrammeBatch $batch, MasterSession $session): Booking
    {
        return DB::transaction(function () use ($user, $course, $batch, $session) {
            $lockedBatch = ProgrammeBatch::with('centre')
                ->where('id', $batch->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedBatch || $lockedBatch->available_slots <= 0) {
                throw new Exception('No available slots for this programme batch.');
            }

            $courseType = Booking::resolveCourseType($course->id);
            $sessionCapacity = $course->centre?->slotCapacityFor($courseType);

            if ($sessionCapacity !== null) {
                $sessionUsed = Booking::where('master_session_id', $session->id)
                    ->lockForUpdate()
                    ->count();

                if ($sessionUsed >= $sessionCapacity) {
                    throw new Exception('Course session is full.');
                }
            }

            $existing = Booking::where('user_id', $user->userId)
                ->where('programme_batch_id', $batch->id)
                ->first();

            if ($existing) {
                return $existing;
            }

            $previous = Booking::where('user_id', $user->userId)
                ->where('programme_batch_id', '!=', $batch->id)
                ->first();

            if ($previous) {
                $this->cancel($previous);
            }

            $lockedBatch->decrement('available_slots');
            AvailabilityService::clearCache(
                $course->centre_id,
                $course->id,
                $lockedBatch->start_date,
                $lockedBatch->end_date
            );

            $admission = UserAdmission::updateOrCreate(
                ['user_id' => $user->userId],
                [
                    'course_id' => $course->id,
                    'batch_id' => $batch->admission_batch_id,
                    'programme_batch_id' => $batch->id,
                    'email_sent' => now(),
                ]
            );

            return Booking::create([
                'user_id' => $user->userId,
                'programme_batch_id' => $batch->id,
                'master_session_id' => $session->id,
                'centre_id' => $lockedBatch->centre_id,
                'course_id' => $course->id,
                'course_type' => $courseType,
                'status' => true,
                'booked_at' => now(),
                'user_admission_id' => $admission->id,
            ]);
        });
    }

    /**
     * Cancel a booking: hard-delete the row, restore the slot if still > 7 days
     * before the batch end, and fire AdmissionSlotFreed so the waitlist is notified.
     *
     * @return bool true if the slot was restored, false if within the 7-day freeze window.
     */
    public function cancel(Booking $booking): bool
    {
        $batch = $booking->programmeBatch;
        if (!$batch) {
            $booking->delete();
            return false;
        }

        $slotRestored = false;
        $daysUntilEnd = now()->diffInDays(Carbon::parse($batch->end_date), false);

        if ($daysUntilEnd > 7) {
            DB::transaction(function () use ($batch) {
                $lockedBatch = ProgrammeBatch::where('id', $batch->id)
                    ->lockForUpdate()
                    ->first();

                if ($lockedBatch) {
                    $lockedBatch->increment('available_slots');
                }
            });

            AvailabilityService::clearCache(
                $batch->centre_id,
                $booking->course_id,
                $batch->start_date,
                $batch->end_date
            );

            $slotRestored = true;
            event(new AdmissionSlotFreed($batch, $booking));
        }

        $booking->delete();

        return $slotRestored;
    }
}
