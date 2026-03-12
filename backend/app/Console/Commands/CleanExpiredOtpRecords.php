<?php

namespace App\Console\Commands;

use App\Models\OtpVerifiedEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Purge stale rows from the otp_verified_emails table.
 *
 * Three categories are deleted:
 *
 *  1. **Expired & never verified** — `verified_at IS NULL` AND `used_at IS NULL`
 *     AND `expires_at < NOW()`. Abandoned OTP flows where the user never
 *     entered the code. Deleted immediately once the OTP expires.
 *
 *  2. **Verified but never consumed & stale** — `verified_at IS NOT NULL` AND
 *     `used_at IS NULL` AND `verified_at` is older than the VERIFIED_TTL
 *     window (default 30 minutes). The user verified their email but never
 *     submitted the registration form within the allowed window.
 *
 *  3. **Consumed & stale** — `used_at IS NOT NULL` AND `used_at` is older
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

        // Default verified TTL from config (seconds), fallback 1800s = 30 min
        $verifiedTtlSeconds = (int) (config('OTP_VERIFIED_TTL') ?? 1800);

        // ── 1. Expired, never verified, never consumed ──────────────
        // Abandoned OTP flows where the user never entered the code.
        $expiredUnverified = OtpVerifiedEmail::whereNull('used_at')
            ->whereNull('verified_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();

        // ── 2. Verified but never consumed & verification window expired ─
        // The user verified their email but never completed registration
        // within the VERIFIED_TTL window. Safe to purge.
        $verifiedStale = OtpVerifiedEmail::whereNull('used_at')
            ->whereNotNull('verified_at')
            ->where('verified_at', '<', now()->subSeconds($verifiedTtlSeconds))
            ->delete();

        // ── 3. Consumed & stale (past grace period) ─────────────────
        $consumedStale = OtpVerifiedEmail::whereNotNull('used_at')
            ->where('used_at', '<', now()->subHours($graceHours))
            ->delete();

        $total = $expiredUnverified + $verifiedStale + $consumedStale;

        if ($total > 0) {
            $this->info("Purged {$total} OTP record(s): {$expiredUnverified} expired-unverified, {$verifiedStale} verified-stale, {$consumedStale} consumed-stale.");
            Log::info('OTP cleanup: purged records', [
                'expired_unverified' => $expiredUnverified,
                'verified_stale'     => $verifiedStale,
                'consumed_stale'     => $consumedStale,
            ]);
        } else {
            $this->info('No stale OTP records to purge.');
        }

        return self::SUCCESS;
    }
}
