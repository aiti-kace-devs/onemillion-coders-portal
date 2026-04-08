<?php

namespace App\Support;

/**
 * Canonical partner_code: lowercase slug (letters, digits, underscore, hyphen).
 */
final class PartnerCodeNormalizer
{
    public static function normalize(string $code): string
    {
        $code = strtolower(trim($code));
        if ($code === '') {
            return '';
        }
        $code = preg_replace('/[^a-z0-9_-]+/', '-', $code) ?? $code;

        return trim((string) $code, '-');
    }
}
