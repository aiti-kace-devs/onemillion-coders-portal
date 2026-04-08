<?php

namespace App\Console\Commands;

use App\Helpers\MailerHelper;
use App\Models\StudentPartnerProgress;
use App\Services\PartnerProgressStalenessService;
use App\Services\Partners\PartnerRegistry;
use Illuminate\Console\Command;

class SendStalePartnerProgressReminders extends Command
{
    protected $signature = 'partner:send-stale-progress-reminders';
    protected $description = 'Send reminder emails for stale partner learning progress';

    public function handle(PartnerProgressStalenessService $stalenessService, PartnerRegistry $registry): int
    {
        if (!(bool) config('services.partner_progress.send_stale_reminders', true)) {
            $this->info('Partner stale reminders disabled by config.');
            return self::SUCCESS;
        }

        $partnerCodes = array_keys($registry->all());
        if ($partnerCodes === []) {
            $this->info('No partner progress drivers registered; nothing to remind.');
            return self::SUCCESS;
        }

        $limit = (int) config('services.partner_progress.reminder_batch_size', 200);
        $processed = 0;
        $sent = 0;

        StudentPartnerProgress::query()
            ->with(['user', 'course'])
            ->whereIn('partner_code', $partnerCodes)
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
                    $selected = is_array($summary['selected'] ?? null) ? $summary['selected'] : [];
                    $overall = (float) ($row->overall_progress_percent ?? 0);

                    $ok = MailerHelper::sendTemplateEmail(
                        templateName: PARTNER_PROGRESS_STALE_REMINDER_EMAIL,
                        emails: $user->email,
                        data: [
                            'name' => $user->name ?? 'Participant',
                            'course_name' => $row->course?->course_name ?? 'your course',
                            'overall_progress' => number_format($overall, 1) . '%',
                            // Dynamic lines from whatever keys exist in the saved snapshot (partner-normalized).
                            'progress_breakdown' => $this->formatProgressBreakdownForEmail($selected),
                            // Legacy placeholders — still filled when those keys exist in `selected`.
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

    /**
     * HTML line breaks between rows; keys mirror {@see PartnerProgressVisualizationService} labelling.
     *
     * @param  array<string, mixed>  $selected
     */
    private function formatProgressBreakdownForEmail(array $selected): string
    {
        $lines = [];
        foreach ($selected as $key => $value) {
            if (! is_string($key) || ! str_ends_with($key, '_percentage_complete')) {
                continue;
            }
            if (! is_numeric($value)) {
                continue;
            }
            $label = str($key)
                ->replace('_percentage_complete', '')
                ->replace('_', ' ')
                ->title()
                ->toString();
            $lines[] = sprintf('%s: %.1f%%', $label, (float) $value);
        }

        usort($lines, fn (string $a, string $b): int => strcmp($a, $b));

        if ($lines === []) {
            return 'Detailed progress: not available in the last saved snapshot yet.';
        }

        return implode(' <br>', $lines);
    }
}
