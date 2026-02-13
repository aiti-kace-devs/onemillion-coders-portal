<?php

namespace App\Services;

use App\Models\OtpVerifiedEmail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OtpService
{
    /**
     * Cache key prefixes — all OTP keys are scoped to a specific identifier (email).
     * Uses Laravel Cache driver (configurable: file, database, redis, etc.) for deployment flexibility.
     */
    private const PREFIX = 'otp:';
    private const HASH_SUFFIX = ':hash';
    private const ATTEMPTS_SUFFIX = ':attempts';
    private const REQUEST_COUNT_SUFFIX = ':requests';
    private const PHONE_SUFFIX = ':phone';

    /** Defaults used when AppConfig is not set */
    private const DEFAULT_OTP_TTL = 600;
    private const DEFAULT_VERIFIED_TTL = 1800;
    private const DEFAULT_MAX_REQUESTS = 3;
    private const DEFAULT_REQUEST_WINDOW = 600;
    private const DEFAULT_MAX_ATTEMPTS = 5;

    /**
     * OTP time-to-live in seconds. Public so controllers can derive
     * human-readable values (e.g. minutes) without duplicating the logic.
     */
    public function otpTtl(): int
    {
        return (int) (config('OTP_TTL') ?? self::DEFAULT_OTP_TTL);
    }

    /**
     * OTP TTL expressed in whole minutes (rounded up).
     */
    public function otpTtlMinutes(): int
    {
        return (int) ceil($this->otpTtl() / 60);
    }

    private function verifiedTtl(): int
    {
        return (int) (config('OTP_VERIFIED_TTL') ?? self::DEFAULT_VERIFIED_TTL);
    }

    private function maxRequests(): int
    {
        return (int) (config('OTP_MAX_REQUESTS') ?? self::DEFAULT_MAX_REQUESTS);
    }

    private function requestWindow(): int
    {
        return (int) (config('OTP_REQUEST_WINDOW') ?? self::DEFAULT_REQUEST_WINDOW);
    }

    private function maxAttempts(): int
    {
        return (int) (config('OTP_MAX_ATTEMPTS') ?? self::DEFAULT_MAX_ATTEMPTS);
    }

    /**
     * Generate a cryptographically secure 6-digit OTP.
     */
    public function generate(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Store a new OTP for the given identifier (email).
     *
     * Writes to TWO stores:
     *  1. Cache (transient) — hashed OTP, attempt counter, phone, rate-limit counter
     *  2. otp_verified_emails (persistent) — lifecycle row created at send-time
     *     with otp_code_hash, expires_at, verified_at=null, used_at=null
     *
     * The persistent row serves as:
     *  - A real-time "email in-flight" lock (blocks other users)
     *  - An audit trail (otp_code_hash proves the row came from a legitimate send)
     *  - A verification state tracker independent of cache eviction
     */
    public function store(string $identifier, string $otp, ?string $phone = null): void
    {
        $email = $this->normalizeEmail($identifier);
        $key = $this->key($identifier);
        $hashedOtp = Hash::make($otp);
        $ttl = $this->otpTtl();
        $expiresAt = now()->addSeconds($ttl);

        // ── Cache (transient) ──────────────────────────────────
        // Store hashed OTP with expiration timestamp for getRemainingTtl
        $hashData = ['hash' => $hashedOtp, 'expires_at' => time() + $ttl];
        Cache::put($key . self::HASH_SUFFIX, $hashData, $ttl);

        // Reset attempt counter
        Cache::put($key . self::ATTEMPTS_SUFFIX, 0, $ttl);

        if ($phone) {
            Cache::put($key . self::PHONE_SUFFIX, $phone, $ttl);
        } else {
            Cache::forget($key . self::PHONE_SUFFIX);
        }

        // Rate-limit: increment request count (sliding window)
        $requestKey = $key . self::REQUEST_COUNT_SUFFIX;
        $existing = Cache::get($requestKey);
        $count = $existing ? (int) ($existing['count'] ?? 0) + 1 : 1;
        Cache::put($requestKey, ['count' => $count, 'expires_at' => time() + $this->requestWindow()], $this->requestWindow());

        // ── Persistent (otp_verified_emails) ───────────────────
        // Upsert at send-time — creates the lifecycle row.
        // verified_at is reset to null (new OTP invalidates previous verification).
        // used_at is reset to null (new OTP invalidates previous consumption).
        OtpVerifiedEmail::updateOrCreate(
            ['email' => $email],
            [
                'otp_code_hash' => $hashedOtp,
                'expires_at'    => $expiresAt,
                'verified_at'   => null,
                'used_at'       => null,
            ]
        );
    }

    /**
     * Verify an OTP code for the given identifier.
     *
     * @return array{success: bool, message: string, remaining_attempts?: int}
     */
    public function verify(string $identifier, string $otp): array
    {
        $key = $this->key($identifier);

        // Check if already verified (persistent table)
        if ($this->isVerified($identifier)) {
            return ['success' => true, 'message' => 'Already verified.'];
        }

        $hashData = Cache::get($key . self::HASH_SUFFIX);
        if (!$hashData || !isset($hashData['hash'])) {
            return ['success' => false, 'message' => 'OTP has expired or was not requested. Please request a new one.'];
        }

        $attempts = (int) (Cache::get($key . self::ATTEMPTS_SUFFIX) ?? 0);
        if ($attempts >= $this->maxAttempts()) {
            $this->invalidate($identifier);
            return ['success' => false, 'message' => 'Too many failed attempts. Please request a new OTP.'];
        }

        if (!Hash::check($otp, $hashData['hash'])) {
            Cache::put($key . self::ATTEMPTS_SUFFIX, $attempts + 1, $this->otpTtl());
            $remaining = $this->maxAttempts() - $attempts - 1;
            return [
                'success' => false,
                'message' => "Invalid OTP code. {$remaining} attempt(s) remaining.",
                'remaining_attempts' => $remaining,
            ];
        }

        // Mark as verified in the persistent DB FIRST, then clear cache.
        // If the DB write fails (no lifecycle row), don't clear cache and report the failure.
        $dbUpdated = $this->markVerified($identifier);

        if (!$dbUpdated) {
            return [
                'success' => false,
                'message' => 'Verification could not be completed. Please request a new OTP.',
            ];
        }

        // DB write succeeded — safe to clear transient cache
        Cache::forget($key . self::HASH_SUFFIX);
        Cache::forget($key . self::ATTEMPTS_SUFFIX);

        return ['success' => true, 'message' => 'Verification successful.'];
    }

    /**
     * Mark the identifier as verified (persistent DB).
     *
     * Updates the existing lifecycle row (created during store()).
     * Explicitly resets used_at to null so re-verification after an
     * abandoned registration works correctly.
     *
     * If no row exists, this is a no-op — a row MUST be created by store()
     * first (which sets otp_code_hash as the legitimacy proof). Creating a
     * row here without otp_code_hash would fail the checks in FormResponseController.
     *
     * @return bool True if the DB row was updated, false if no row existed.
     */
    public function markVerified(string $identifier): bool
    {
        $email = $this->normalizeEmail($identifier);

        $updated = OtpVerifiedEmail::where('email', $email)
            ->update(['verified_at' => now(), 'used_at' => null]);

        if ($updated === 0) {
            Log::warning(
                'markVerified called for email with no lifecycle row — OTP was never sent via store()',
                ['email' => $email]
            );
            return false;
        }

        return true;
    }

    /**
     * Check if the identifier has been verified and not yet consumed.
     *
     * Uses persistent table so it works across servers and survives cache eviction.
     * Checks:
     *  1. Row exists
     *  2. otp_code_hash is present (proves the row was created by store(), not fabricated)
     *  3. verified_at is set (OTP was actually verified)
     *  4. used_at is null (verification hasn't been consumed by a registration)
     *  5. Verification is within the configured TTL
     */
    public function isVerified(string $identifier): bool
    {
        $email = $this->normalizeEmail($identifier);
        $record = OtpVerifiedEmail::where('email', $email)->first();

        if (!$record || empty($record->otp_code_hash)) {
            return false;
        }

        if ($record->verified_at === null || $record->used_at !== null) {
            return false;
        }

        $verifiedTtl = $this->verifiedTtl();
        $elapsed = now()->timestamp - $record->verified_at->timestamp;
        return $elapsed < $verifiedTtl;
    }

    /**
     * Consume a verification (mark as used). Call after successful registration.
     * Prevents reuse of the same verification from Postman/curl etc.
     *
     * @return bool True if the row was updated, false otherwise.
     */
    public function consumeVerification(string $identifier): bool
    {
        $email = $this->normalizeEmail($identifier);

        $updated = OtpVerifiedEmail::where('email', $email)->update(['used_at' => now()]);

        if ($updated === 0) {
            Log::warning('consumeVerification: no row found to consume', ['email' => $email]);
            return false;
        }

        return true;
    }

    /**
     * Check if an email has an active OTP in-flight (sent but not yet expired).
     *
     * This covers two states:
     *  1. OTP sent, not yet verified (verified_at = null, expires_at > now)
     *  2. OTP verified, not yet consumed (verified_at set, within verified TTL)
     *
     * Returns true if the email is "locked" by an active OTP flow.
     */
    public function hasActiveOtp(string $identifier): bool
    {
        $email = $this->normalizeEmail($identifier);
        $record = OtpVerifiedEmail::where('email', $email)->first();

        if (!$record) {
            return false;
        }

        // Already consumed (registration completed) — not active
        if ($record->used_at !== null) {
            return false;
        }

        // State 1: OTP sent, not yet verified — check expires_at
        if ($record->verified_at === null) {
            return $record->expires_at && now()->lessThan($record->expires_at);
        }

        // State 2: Verified, not yet consumed — check verified TTL
        $verifiedTtl = $this->verifiedTtl();
        $elapsed = now()->timestamp - $record->verified_at->timestamp;
        return $elapsed < $verifiedTtl;
    }

    /**
     * Comprehensive email availability check for real-time frontend validation.
     *
     * Runs an opportunistic purge of expired records first, then checks:
     *
     * Returns one of:
     *  - ['available' => true]
     *  - ['available' => false, 'reason' => 'registered']     — already in users table
     *  - ['available' => false, 'reason' => 'used']           — used_at is set (consumed by registration)
     *  - ['available' => false, 'reason' => 'otp_active']     — active OTP flow in progress
     *
     * @return array{available: bool, reason?: string}
     */
    public function checkEmailAvailability(string $identifier): array
    {
        $email = $this->normalizeEmail($identifier);

        // Opportunistic cleanup — keep the table lean on every availability check
        $this->purgeExpiredRecords();

        // Check 1: Already registered in users table?
        if (User::where('email', $email)->exists()) {
            return ['available' => false, 'reason' => 'registered'];
        }

        // Check 2: OTP verification already consumed (used_at IS NOT NULL)?
        // This is a belt-and-suspenders check alongside the users table.
        // If the verification was consumed but the user somehow isn't in the
        // users table (e.g. partial failure), this still blocks re-use.
        $record = OtpVerifiedEmail::where('email', $email)->first();
        if ($record && $record->used_at !== null) {
            return ['available' => false, 'reason' => 'used'];
        }

        // Check 3: Active OTP flow in progress?
        if ($this->hasActiveOtp($email)) {
            return ['available' => false, 'reason' => 'otp_active'];
        }

        return ['available' => true];
    }

    /**
     * Opportunistic purge of expired OTP records.
     *
     * Deletes rows where:
     *  - `used_at IS NULL` AND `expires_at < NOW()` (expired, never consumed)
     *
     * This lightweight cleanup runs inline during email availability checks
     * to keep the table lean without relying solely on the scheduled command.
     * Only targets unused expired records — consumed records are handled by
     * the scheduled `otp:clean` command with a grace period.
     */
    public function purgeExpiredRecords(): void
    {
        try {
            $deleted = OtpVerifiedEmail::whereNull('used_at')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', now())
                ->delete();

            if ($deleted > 0) {
                Log::info("OTP opportunistic cleanup: purged {$deleted} expired-unused record(s).");
            }
        } catch (\Throwable $e) {
            // Non-critical — don't let cleanup failures break the main flow
            Log::warning('OTP opportunistic cleanup failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Invalidate all transient OTP data for the given identifier.
     */
    public function invalidate(string $identifier): void
    {
        $key = $this->key($identifier);
        Cache::forget($key . self::HASH_SUFFIX);
        Cache::forget($key . self::ATTEMPTS_SUFFIX);
        Cache::forget($key . self::PHONE_SUFFIX);
    }

    /**
     * Check rate-limiting — whether this identifier can request another OTP.
     *
     * @return array{allowed: bool, message: string, retry_after?: int}
     */
    public function canRequestOtp(string $identifier): array
    {
        $key = $this->key($identifier);
        $requestKey = $key . self::REQUEST_COUNT_SUFFIX;
        $data = Cache::get($requestKey);

        $count = $data ? (int) ($data['count'] ?? 0) : 0;
        $expiresAt = $data['expires_at'] ?? 0;

        if ($count >= $this->maxRequests()) {
            $retryAfter = max($expiresAt - time(), 0);
            return [
                'allowed' => false,
                'message' => 'Too many OTP requests. Please try again later.',
                'retry_after' => $retryAfter,
            ];
        }

        return ['allowed' => true, 'message' => 'OK'];
    }

    public function getAssociatedPhone(string $identifier): ?string
    {
        $key = $this->key($identifier);
        $phone = Cache::get($key . self::PHONE_SUFFIX);
        return is_string($phone) ? $phone : null;
    }

    /**
     * Get remaining TTL for the current OTP (for frontend countdown).
     */
    public function getRemainingTtl(string $identifier): int
    {
        $key = $this->key($identifier);
        $hashData = Cache::get($key . self::HASH_SUFFIX);
        if (!$hashData || !isset($hashData['expires_at'])) {
            return 0;
        }
        return max($hashData['expires_at'] - time(), 0);
    }

    private function key(string $identifier): string
    {
        return self::PREFIX . $this->normalizeEmail($identifier);
    }

    private function normalizeEmail(string $identifier): string
    {
        return strtolower(trim($identifier));
    }
}
