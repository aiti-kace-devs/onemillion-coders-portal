<?php

namespace App\Console\Commands;

use App\Helpers\MailerHelper;
use App\Models\StudentPartnerProgress;
use App\Services\PartnerProgressStalenessService;
use Illuminate\Console\Command;

class SendStalePartnerProgressReminders extends Command
{
    protected $signature = 'partner:send-stale-progress-reminders';
    protected $description = 'Send reminder emails for stale partner learning progress';

    public function handle(PartnerProgressStalenessService $stalenessService): int
    {
        if (!(bool) config('services.partner_startocode.send_stale_reminders', true)) {
            $this->info('Partner stale reminders disabled by config.');
            return self::SUCCESS;
        }

        $limit = (int) config('services.partner_startocode.reminder_batch_size', 200);
        $partnerCode = (string) config('services.partner_startocode.code', 'startocode');
        $processed = 0;
        $sent = 0;

        StudentPartnerProgress::query()
            ->with(['user', 'course'])
            ->where('partner_code', $partnerCode)
            ->whereNotNull('stale_after_at')
            ->where('stale_after_at', '<=', now())
            ->orderBy('id')
            ->limit($limit)
            ->chunkById(100, function ($rows) use (&$processed, &$sent, $stalenessService) {
                foreach ($rows as $row) {
                    $processed++;
                    if (!$stalenessService->shouldSendReminder($row)) {
                        continue;
                    }

                    $user = $row->user;
                    if (!$user || !$user->email) {
                        continue;
                    }

                    $summary = $row->progress_summary_json ?? [];
                    $selected = $summary['selected'] ?? [];
                    $overall = (float) ($row->overall_progress_percent ?? 0);

                    $ok = MailerHelper::sendTemplateEmail(
                        templateName: PARTNER_PROGRESS_STALE_REMINDER_EMAIL,
                        emails: $user->email,
                        data: [
                            'name' => $user->name ?? 'Participant',
                            'course_name' => $row->course?->course_name ?? 'your course',
                            'overall_progress' => number_format($overall, 1) . '%',
                            'video_progress' => (string) ($selected['video_percentage_complete'] ?? 0),
                            'quiz_progress' => (string) ($selected['quiz_percentage_complete'] ?? 0),
                            'project_progress' => (string) ($selected['project_percentage_complete'] ?? 0),
                            'task_progress' => (string) ($selected['task_percentage_complete'] ?? 0),
                            'last_activity_at' => $row->last_activity_at?->toDateTimeString() ?? 'N/A',
                        ],
                        subject: 'Reminder: Continue your learning progress'
                    );

                    if ($ok) {
                        $row->last_reminder_sent_at = now();
                        $row->reminder_count = ((int) $row->reminder_count) + 1;
                        $row->save();
                        $sent++;
                    }
                }
            });

        $this->info("Processed {$processed} stale progress rows; sent {$sent} reminder email(s).");
        return self::SUCCESS;
    }
}
