<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    /**
     * Redis key prefixes — all OTP keys are scoped to a specific identifier (email).
     * This ensures OTP codes are NEVER interchangeable between users.
     */
    private const PREFIX = 'otp:';
    private const HASH_SUFFIX = ':hash';
    private const VERIFIED_SUFFIX = ':verified';
    private const ATTEMPTS_SUFFIX = ':attempts';
    private const REQUEST_COUNT_SUFFIX = ':requests';
    private const PHONE_SUFFIX = ':phone';

    /** OTP validity duration in seconds (10 minutes) */
    private const OTP_TTL = 600;

    /** Verified status TTL in seconds (30 minutes) — enough time to complete the form */
    private const VERIFIED_TTL = 1800;

    /** Max OTP send requests per identifier within the rate-limit window */
    private const MAX_REQUESTS = 3;

    /** Rate-limit window in seconds (10 minutes) */
    private const REQUEST_WINDOW = 600;

    /** Max wrong-code attempts per OTP before it's invalidated */
    private const MAX_ATTEMPTS = 5;

    /**
     * Generate a cryptographically secure 6-digit OTP.
     * Uses random_int() which is CSPRNG-backed — not guessable.
     */
    public function generate(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Store a new OTP for the given identifier (email).
     * The OTP is hashed before storage — plain text is never persisted.
     * The identifier scopes the OTP so it cannot be used by another user.
     *
     * @param string      $identifier  The user's email address
     * @param string      $otp         The plain-text 6-digit OTP
     * @param string|null $phone       Optional phone number to associate
     */
    public function store(string $identifier, string $otp, ?string $phone = null): void
    {
        $key = $this->key($identifier);

        // Hash the OTP before storing — even if Redis is compromised, codes are safe
        $hashedOtp = Hash::make($otp);

        $redis = Redis::connection('default');

        // Store hashed OTP with TTL
        $redis->setex($key . self::HASH_SUFFIX, self::OTP_TTL, $hashedOtp);

        // Reset attempt counter for this new OTP
        $redis->setex($key . self::ATTEMPTS_SUFFIX, self::OTP_TTL, 0);

        // Clear any prior verified status since a new OTP was requested
        $redis->del($key . self::VERIFIED_SUFFIX);

        // Store associated phone number if provided, otherwise clear any stale one
        if ($phone) {
            $redis->setex($key . self::PHONE_SUFFIX, self::OTP_TTL, $phone);
        } else {
            $redis->del($key . self::PHONE_SUFFIX);
        }

        // Increment the request counter for rate-limiting
        $requestKey = $key . self::REQUEST_COUNT_SUFFIX;
        if (!$redis->exists($requestKey)) {
            $redis->setex($requestKey, self::REQUEST_WINDOW, 1);
        } else {
            $redis->incr($requestKey);
        }
    }

    /**
     * Verify an OTP code for the given identifier.
     * The code is checked against the HASHED value stored for THIS specific identifier.
     * A code valid for user-A will NOT verify for user-B — scoped by identifier.
     *
     * @param string $identifier The user's email address
     * @param string $otp        The plain-text OTP the user entered
     * @return array{success: bool, message: string, remaining_attempts?: int}
     */
    public function verify(string $identifier, string $otp): array
    {
        $key = $this->key($identifier);
        $redis = Redis::connection('default');

        // Check if already verified
        if ($redis->exists($key . self::VERIFIED_SUFFIX)) {
            return ['success' => true, 'message' => 'Already verified.'];
        }

        // Check if OTP exists (not expired)
        $hashedOtp = $redis->get($key . self::HASH_SUFFIX);
        if (!$hashedOtp) {
            return ['success' => false, 'message' => 'OTP has expired or was not requested. Please request a new one.'];
        }

        // Check attempt limit
        $attempts = (int) $redis->get($key . self::ATTEMPTS_SUFFIX);
        if ($attempts >= self::MAX_ATTEMPTS) {
            $this->invalidate($identifier);
            return ['success' => false, 'message' => 'Too many failed attempts. Please request a new OTP.'];
        }

        // Verify the OTP hash — this is scoped to the identifier, so another user's OTP won't match
        if (!Hash::check($otp, $hashedOtp)) {
            $redis->incr($key . self::ATTEMPTS_SUFFIX);
            $remaining = self::MAX_ATTEMPTS - $attempts - 1;
            return [
                'success' => false,
                'message' => "Invalid OTP code. {$remaining} attempt(s) remaining.",
                'remaining_attempts' => $remaining,
            ];
        }

        // OTP is correct — mark as verified and clean up the code
        $this->markVerified($identifier);
        $redis->del($key . self::HASH_SUFFIX);
        $redis->del($key . self::ATTEMPTS_SUFFIX);

        return ['success' => true, 'message' => 'Verification successful.'];
    }

    /**
     * Mark the identifier as verified in Redis.
     */
    public function markVerified(string $identifier): void
    {
        $key = $this->key($identifier);
        Redis::connection('default')->setex($key . self::VERIFIED_SUFFIX, self::VERIFIED_TTL, '1');
    }

    /**
     * Check if the identifier has been verified.
     */
    public function isVerified(string $identifier): bool
    {
        $key = $this->key($identifier);
        return (bool) Redis::connection('default')->exists($key . self::VERIFIED_SUFFIX);
    }

    /**
     * Invalidate (delete) all OTP data for the given identifier.
     */
    public function invalidate(string $identifier): void
    {
        $key = $this->key($identifier);
        $redis = Redis::connection('default');

        $redis->del($key . self::HASH_SUFFIX);
        $redis->del($key . self::ATTEMPTS_SUFFIX);
        $redis->del($key . self::PHONE_SUFFIX);
        // Note: we intentionally do NOT delete the verified key or request count
    }

    /**
     * Check rate-limiting — whether this identifier can request another OTP.
     *
     * @return array{allowed: bool, message: string, retry_after?: int}
     */
    public function canRequestOtp(string $identifier): array
    {
        $key = $this->key($identifier);
        $redis = Redis::connection('default');

        $requestKey = $key . self::REQUEST_COUNT_SUFFIX;
        $count = (int) $redis->get($requestKey);

        if ($count >= self::MAX_REQUESTS) {
            $ttl = $redis->ttl($requestKey);
            return [
                'allowed' => false,
                'message' => 'Too many OTP requests. Please try again later.',
                'retry_after' => max($ttl, 0),
            ];
        }

        return ['allowed' => true, 'message' => 'OK'];
    }

    /**
     * Get the phone number associated with an OTP request.
     */
    public function getAssociatedPhone(string $identifier): ?string
    {
        $key = $this->key($identifier);
        $phone = Redis::connection('default')->get($key . self::PHONE_SUFFIX);
        return $phone ?: null;
    }

    /**
     * Get remaining TTL for the current OTP (for frontend countdown).
     */
    public function getRemainingTtl(string $identifier): int
    {
        $key = $this->key($identifier);
        $ttl = Redis::connection('default')->ttl($key . self::HASH_SUFFIX);
        return max($ttl, 0);
    }

    /**
     * Build a namespaced Redis key from the identifier.
     * The identifier is normalized to lowercase to avoid case-sensitivity issues.
     */
    private function key(string $identifier): string
    {
        return self::PREFIX . strtolower(trim($identifier));
    }
}
