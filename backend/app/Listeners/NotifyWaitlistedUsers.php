<?php

namespace App\Listeners;

use App\Events\AdmissionSlotFreed;
use App\Events\CourseBatchCreated;
use App\Models\AdmissionWaitlist;
use App\Models\AppConfig;
use App\Models\Course;
use App\Notifications\WaitlistSlotAvailableNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyWaitlistedUsers implements ShouldQueue
{
    public string $queue = 'notifications';

    /**
     * Handle AdmissionSlotFreed and CourseBatchCreated events.
     */
    public function handle(AdmissionSlotFreed|CourseBatchCreated $event): void
    {
        $courseId = $this->resolveCourseId($event);

        if (!$courseId) {
            return;
        }

        $course = Course::find($courseId);
        if (!$course) {
            return;
        }

        $limit = (int) AppConfig::getValue('WAITLIST_NOTIFICATION_COUNT', 5);

        $entries = AdmissionWaitlist::where('course_id', $courseId)
            ->whereNull('notified_at')
            ->orderBy('created_at')
            ->limit($limit)
            ->with('user')
            ->get();

        foreach ($entries as $entry) {
            $user = $entry->user;
            if (!$user) {
                continue;
            }

            $user->notify(new WaitlistSlotAvailableNotification($course));

            $entry->update(['notified_at' => now()]);
        }
    }

    private function resolveCourseId(AdmissionSlotFreed|CourseBatchCreated $event): ?int
    {
        if ($event instanceof AdmissionSlotFreed) {
            return $event->courseId;
        }

        // CourseBatchCreated
        return $event->courseBatch->course_id;
    }
}
