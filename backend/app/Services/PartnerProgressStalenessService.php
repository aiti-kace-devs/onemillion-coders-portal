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

        $cooldownHours = (int) config('services.partner_startocode.reminder_cooldown_hours', 24);
        if (!$progress->last_reminder_sent_at) {
            return true;
        }

        return $progress->last_reminder_sent_at->copy()->addHours($cooldownHours)->lte(now());
    }
}
