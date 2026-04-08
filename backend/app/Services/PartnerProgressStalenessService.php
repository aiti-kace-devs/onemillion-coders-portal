<?php

namespace App\Services;

use App\Models\StudentPartnerProgress;
use Carbon\Carbon;

class PartnerProgressStalenessService
{
    public function isStale(StudentPartnerProgress $progress, ?Carbon $now = null): bool
    {
        $now = $now ?: now();
        if (!$progress->stale_after_at) {
            return false;
        }

        return $progress->stale_after_at->lte($now);
    }

    public function shouldSendReminder(StudentPartnerProgress $progress): bool
    {
        if (!$this->isStale($progress)) {
            return false;
        }

        if (!$progress->user || !$progress->user->email) {
            return false;
        }

        $cooldownHours = $this->reminderCooldownHours();
        if (!$progress->last_reminder_sent_at) {
            return true;
        }

        return $progress->last_reminder_sent_at->copy()->addHours($cooldownHours)->lte(now());
    }

    /**
     * Admin App Config {@see PARTNER_PROGRESS_REMINDER_COOLDOWN_HOURS} overrides
     * {@see config('services.partner_progress.reminder_cooldown_hours')} / env.
     */
    private function reminderCooldownHours(): int
    {
        $fromApp = config('PARTNER_PROGRESS_REMINDER_COOLDOWN_HOURS');
        if ($fromApp !== null && $fromApp !== '') {
            return (int) $fromApp;
        }

        return (int) config('services.partner_progress.reminder_cooldown_hours', 24);
    }
}
