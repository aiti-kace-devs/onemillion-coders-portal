<?php

namespace App\Listeners;

use App\Events\AdmissionSlotFreed;
use App\Events\ProgrammeBatchCreated;
use App\Models\AppConfig;
use App\Models\UserAdmission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

class NotifyWaitlistedUsers
{
    /**
     * Handle AdmissionSlotFreed event.
     */
    public function onSlotFreed(AdmissionSlotFreed $event): void
    {
        $this->notifyWaitlistedUsers($event->programmeBatch);
    }

    /**
     * Handle ProgrammeBatchCreated event.
     */
    public function onBatchCreated(ProgrammeBatchCreated $event): void
    {
        foreach ($event->batches as $batch) {
            $this->notifyWaitlistedUsers($batch);
        }
    }

    /**
     * Notify the first N waitlisted users for a programme batch.
     */
    private function notifyWaitlistedUsers($programmeBatch): void
    {
        // Guard: skip if admission_waitlist table doesn't exist
        if (!Schema::hasTable('admission_waitlist')) {
            Log::warning('Skipping waitlist notification: admission_waitlist table does not exist', [
                'batch_id' => $programmeBatch->id,
            ]);
            return;
        }

        $limit = (int) AppConfig::getValue('WAITLIST_NOTIFY_LIMIT', 5);

        $programme = $programmeBatch->programme;
        if (!$programme) {
            return;
        }

        // Find waitlist entries — using the course linked to the programme
        $waitlistQuery = \Illuminate\Support\Facades\DB::table('admission_waitlist')
            ->join('courses', 'admission_waitlist.course_id', '=', 'courses.id')
            ->where('courses.programme_id', $programme->id)
            ->where('courses.centre_id', $programmeBatch->centre_id)
            ->orderBy('admission_waitlist.created_at', 'asc')
            ->limit($limit)
            ->select('admission_waitlist.user_id', 'admission_waitlist.course_id', 'admission_waitlist.id as waitlist_id')
            ->get();

        if ($waitlistQuery->isEmpty()) {
            return;
        }

        // Batch-load all users in a single query (avoid N+1)
        $userIds = $waitlistQuery->pluck('user_id')->unique()->toArray();
        $usersById = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');

        foreach ($waitlistQuery as $entry) {
            $user = $usersById->get($entry->user_id);
            if (!$user || !$user->email) {
                continue;
            }

            try {
                $user->notify(new \App\Notifications\WaitlistSlotAvailableNotification(
                    $programmeBatch,
                    $entry->course_id
                ));

                Log::info("Waitlist notification sent", [
                    'user_id' => $user->id,
                    'batch_id' => $programmeBatch->id,
                    'waitlist_id' => $entry->waitlist_id,
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to send waitlist notification", [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Register the listeners for events.
     */
    public function subscribe($events): array
    {
        return [
            AdmissionSlotFreed::class => 'onSlotFreed',
            ProgrammeBatchCreated::class => 'onBatchCreated',
        ];
    }
}
