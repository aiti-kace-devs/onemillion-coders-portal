<?php

namespace App\Support;

/**
 * Canonical partner_code for the bundled Startocode HTTP driver.
 * Admin sets {@see PARTNER_PROGRESS_STARTOCODE_PARTNER_CODE} (App Config) or env
 * {@see PARTNER_PROGRESS_STARTOCODE_PARTNER_CODE}; value is normalized like DB partner_code.
 *
 * Programme {@code provider}, {@code partner_integrations.partner_code}, and
 * {@code partner_course_mappings.partner_code} should use the same slug (case-insensitive).
 */
final class StartocodePartnerCode
{
    public const FALLBACK = 'startocode';

    public static function current(): string
    {
        $raw = config('PARTNER_PROGRESS_STARTOCODE_PARTNER_CODE');
        if (! is_string($raw) || trim($raw) === '') {
            $raw = (string) config('services.partner_progress.startocode_partner_code', self::FALLBACK);
        }
        $n = PartnerCodeNormalizer::normalize($raw);

        return $n !== '' ? $n : self::FALLBACK;
    }

    public static function matches(string $partnerCode): bool
    {
        return PartnerCodeNormalizer::normalize($partnerCode) === self::current();
    }
}
