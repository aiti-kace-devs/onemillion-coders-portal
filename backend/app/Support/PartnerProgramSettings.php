<?php

namespace App\Support;

/**
 * Operational defaults for bulk/program sync when not stored on PartnerIntegration rows.
 */
class PartnerProgramSettings
{
    public static function programSlugForPartner(string $partnerCode): string
    {
        $map = config('services.partner_progress.program_slugs_by_partner', []);
        if (!is_array($map)) {
            $map = [];
        }
        $slug = $map[$partnerCode] ?? null;
        if (is_string($slug) && trim($slug) !== '') {
            return trim($slug);
        }

        return (string) config('services.partner_progress.program_slug', 'gh-program');
    }
}
