<?php

namespace App\Listeners;

use App\Events\AdmissionDeleted;
use App\Models\AdmissionWaitlist;
use App\Models\Course;
use App\Models\User;
use App\Notifications\WaitlistCourseSlotAvailableNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class NotifyWaitlistedUsersOnAdmissionDeleted
{
    public function handle(AdmissionDeleted $event): void
    {
        if (!Schema::hasTable('admission_waitlist')) {
            return;
        }

        $course = Course::query()->find($event->courseId);
        if (! $course) {
            return;
        }

        $waitlistEntries = AdmissionWaitlist::query()
            ->pending()
            ->where('course_id', $event->courseId)
            ->oldestFirst()
            ->limit(5)
            ->get(['id', 'user_id', 'course_id']);

        if ($waitlistEntries->isEmpty()) {
            return;
        }

        $usersByUserId = User::query()
            ->whereIn('userId', $waitlistEntries->pluck('user_id')->unique()->values())
            ->get(['id', 'userId', 'name', 'email'])
            ->keyBy('userId');

        foreach ($waitlistEntries as $entry) {
            $user = $usersByUserId->get($entry->user_id);
            if (! $user || ! $user->email) {
                continue;
            }

            try {
                $user->notify(new WaitlistCourseSlotAvailableNotification($course));
                $entry->markAsNotified();
            } catch (\Throwable $e) {
                Log::error('Failed to send admission waitlist email', [
                    'course_id' => $event->courseId,
                    'waitlist_id' => $entry->id,
                    'user_id' => $entry->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
