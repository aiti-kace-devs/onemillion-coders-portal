<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use ReallySimpleJWT\Token;

class JwtService
{
    private const DEFAULT_TTL_SECONDS = 14400; // 4 hours

    private const ISSUER = 'app';

    /** Cache key prefix for logout invalidation; keep long enough to cover token TTL. */
    private const LOGOUT_CACHE_PREFIX = 'jwt_logout_';

    /**
     * ReallySimpleJWT EncodeHS256Strong requires: min 12 chars, at least one digit,
     * one upper, one lower, and one of *&!@%^#$
     */
    private const STRONG_SECRET_SUFFIX = 'A1a*';

    public function __construct(
        private readonly string $secret,
        private readonly int $ttl = self::DEFAULT_TTL_SECONDS
    ) {
    }

    /**
     * Create a new instance from config.
     * JWT TTL defaults to session lifetime so the token expires with the session.
     */
    public static function fromConfig(): self
    {
        $secret = config('app.jwt_token', config('app.key')) ?? '';

        $ttl = config('app.jwt_ttl');
        if ($ttl === null || $ttl === '') {
            $ttl = (int) config('session.lifetime', 120) * 60; // session lifetime in seconds
        } else {
            $ttl = (int) $ttl;
        }
        if ($ttl <= 0) {
            $ttl = self::DEFAULT_TTL_SECONDS;
        }

        return new self(
            secret: self::ensureStrongSecret($secret),
            ttl: $ttl
        );
    }

    /**
     * Invalidate all JWTs for the given user that were issued before now (e.g. on logout).
     * Tokens issued after the next login will still be valid.
     *
     * @param int|string $userId
     */
    public function invalidateForUser(int|string $userId): void
    {
        $key = self::LOGOUT_CACHE_PREFIX . $userId;
        Cache::put($key, time(), now()->addSeconds($this->ttl * 2));
    }

    /**
     * Ensure the secret meets ReallySimpleJWT strong secret rules (min 12 chars,
     * at least one digit, upper, lower, and one of *&!@%^#$).
     */
    private static function ensureStrongSecret(string $secret): string
    {
        if (preg_match('/^.*(?=.{12,}+)(?=.*\d+)(?=.*[A-Z]+)(?=.*[a-z]+)(?=.*[\*&!@%\^#\$]+).*$/', $secret)) {
            return $secret;
        }

        return str_pad($secret, 12, self::STRONG_SECRET_SUFFIX, STR_PAD_RIGHT);
    }

    /**
     * Generate a JWT containing the given user id.
     *
     * @param int|string $userId
     */
    public function generate(int|string $userId): string
    {
        $expiration = time() + $this->ttl;

        return Token::create($userId, $this->secret, $expiration, self::ISSUER);
    }

    /**
     * Validate the token and return the user id from the payload, or null if invalid/expired.
     *
     * @return int|string|null User id or null when token is invalid or expired
     */
    public function validate(string $token): int|string|null
    {
        if ($token === '') {
            return null;
        }

        if (! Token::validate($token, $this->secret)) {
            return null;
        }

        if (! Token::validateExpiration($token)) {
            return null;
        }

        $payload = Token::getPayload($token);
        $userId = $payload['user_id'] ?? null;

        if ($userId === null) {
            return null;
        }

        $userId = is_numeric($userId) ? (int) $userId : $userId;

        $logoutAt = Cache::get(self::LOGOUT_CACHE_PREFIX . $userId);
        if ($logoutAt !== null) {
            $iat = $payload['iat'] ?? ($payload['exp'] ?? time()) - $this->ttl;
            if ($iat < $logoutAt) {
                return null;
            }
        }

        return $userId;
    }
}
