<?php

namespace App\Services;

use ReallySimpleJWT\Token;

class JwtService
{
    /** Far-future exp so token effectively never expires (library requires exp claim). */
    private const NO_EXPIRATION = 2147483647; // max 32-bit signed timestamp (year 2038+)

    private const ISSUER = 'app';

    /**
     * ReallySimpleJWT EncodeHS256Strong requires: min 12 chars, at least one digit,
     * one upper, one lower, and one of *&!@%^#$
     */
    private const STRONG_SECRET_SUFFIX = 'A1a*';

    public function __construct(private readonly string $secret)
    {
    }

    /**
     * Create a new instance from config.
     */
    public static function fromConfig(): self
    {
        $secret = config('app.jwt_token', config('app.key')) ?? '';

        return new self(secret: self::ensureStrongSecret($secret));
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
        return Token::create($userId, $this->secret, self::NO_EXPIRATION, self::ISSUER);
    }

    /**
     * Validate the token and return the user id from the payload, or null if invalid.
     *
     * @return int|string|null User id or null when token is invalid
     */
    public function validate(string $token): int|string|null
    {
        if ($token === '') {
            return null;
        }

        if (! Token::validate($token, $this->secret)) {
            return null;
        }

        $payload = Token::getPayload($token);
        $userId = $payload['user_id'] ?? null;

        if ($userId === null) {
            return null;
        }

        return is_numeric($userId) ? (int) $userId : $userId;
    }
}
