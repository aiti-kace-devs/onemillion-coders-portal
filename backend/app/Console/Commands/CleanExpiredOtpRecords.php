<?php

namespace App\Console\Commands;

use App\Models\OtpVerifiedEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Purge stale rows from the otp_verified_emails table.
 *
 * Two categories are deleted:
 *  1. **Expired & unused** — `used_at IS NULL` AND `expires_at < NOW()`.
 *     These are abandoned OTP flows (user never completed registration).
 *     Deleted immediately once the OTP expires.
 *
 *  2. **Consumed & stale** — `used_at IS NOT NULL` AND `used_at` is older
 *     than a configurable grace period (default 24 hours).
 *     Once the registration is complete, the `users` table is the
 *     authoritative source for "email taken" — the otp_verified_emails
 *     row becomes redundant after the grace window.
 *
 * This command can run on a schedule (recommended: every minute) or be
 * invoked manually via `php artisan otp:clean`.
 */
class CleanExpiredOtpRecords extends Command
{
    protected $signature   = 'otp:clean {--grace=24 : Hours to keep consumed records before purging}';
    protected $description = 'Delete expired / stale OTP verification records';

    public function handle(): int
    {
        $graceHours = (int) $this->option('grace');

        // ── 1. Expired & unused ─────────────────────────────────────
        $expiredUnused = OtpVerifiedEmail::whereNull('used_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();

        // ── 2. Consumed & stale (past grace period) ─────────────────
        $consumedStale = OtpVerifiedEmail::whereNotNull('used_at')
            ->where('used_at', '<', now()->subHours($graceHours))
            ->delete();

        $total = $expiredUnused + $consumedStale;

        if ($total > 0) {
            $this->info("Purged {$total} OTP record(s): {$expiredUnused} expired-unused, {$consumedStale} consumed-stale.");
            Log::info('OTP cleanup: purged records', [
                'expired_unused' => $expiredUnused,
                'consumed_stale' => $consumedStale,
            ]);
        } else {
            $this->info('No stale OTP records to purge.');
        }

        return self::SUCCESS;
    }
}
