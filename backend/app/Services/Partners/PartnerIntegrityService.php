<?php

namespace App\Services\Partners;

use App\Models\PartnerCourseMapping;
use App\Models\PartnerIntegration;
use App\Support\PartnerCodeNormalizer;
use Illuminate\Support\Facades\Schema;

class PartnerIntegrityService
{
    public function __construct(
        private readonly PartnerRegistry $registry,
        private readonly PartnerIntegrationRequestSigner $signer
    ) {
    }

    /**
     * @return list<string>
     */
    public function issuesForPartnerCode(string $partnerCode): array
    {
        $code = PartnerCodeNormalizer::normalize($partnerCode);
        if ($code === '') {
            return ['partner_code is empty'];
        }

        $issues = [];

        if (! Schema::hasTable('partner_integrations')) {
            return ['partner_integrations table missing'];
        }

        $integration = PartnerIntegration::query()->where('partner_code', $code)->first();
        if (! $integration) {
            $issues[] = 'no partner_integrations row for this code';
        } elseif (! $integration->is_enabled) {
            $issues[] = 'partner integration exists but is disabled';
        } elseif (trim((string) $integration->base_url) === '') {
            $issues[] = 'partner integration base_url is empty';
        }

        if (Schema::hasTable('partner_course_mappings')) {
            $hasMapping = PartnerCourseMapping::query()
                ->where('partner_code', $code)
                ->where('is_active', true)
                ->exists();
            if (! $hasMapping) {
                $issues[] = 'no active partner_course_mappings for this code';
            }
        }

        if (! $this->registry->has($code)) {
            $issues[] = 'no resolvable driver (bundled or enabled generic integration)';
        }

        return $issues;
    }

    /**
     * @return list<string>
     */
    public function issuesForIntegration(PartnerIntegration $integration): array
    {
        $issues = $this->issuesForPartnerCode((string) $integration->partner_code);

        foreach ($this->integrationConfigIssues($integration) as $issue) {
            $issues[] = $issue;
        }

        return array_values(array_unique($issues));
    }

    /**
     * Human-readable config problems (works for any partner; avoids one-off assumptions except common header names).
     *
     * @return list<string>
     */
    public function integrationConfigIssues(PartnerIntegration $integration): array
    {
        $out = [];

        $normHeaders = $this->signer->normalizeHeadersFromIntegrationJson($integration->headers_json ?? null);
        $implicitSigning = $this->signer->findDerivedSignatureSecretCandidate($integration, $normHeaders) !== null;

        $signature = is_array($integration->signature_config_json ?? null) ? $integration->signature_config_json : null;
        $hasSigningScheme = is_array($signature)
            && trim((string) ($signature['scheme'] ?? '')) !== '';
        $runtimeSigning = $hasSigningScheme || $implicitSigning;

        foreach ($normHeaders as $name => $value) {
            if (! is_string($name)) {
                continue;
            }
            $lower = strtolower($name);
            if ($lower === 'x-partner-timestamp' && ! $runtimeSigning) {
                if (! ($integration->refresh_timestamp_header_without_signing ?? false)) {
                    $out[] = 'Headers JSON includes X-Partner-Timestamp but Signature config is empty. OMCP will send a fixed time forever — use Signature config (request signing), enable “Refresh timestamp header (no HMAC)”, or remove that header.';
                }
            }
        }

        foreach (['X-Partner-Signature', 'x-partner-signature'] as $sigHeader) {
            if (! array_key_exists($sigHeader, $normHeaders)) {
                continue;
            }
            $v = $normHeaders[$sigHeader];
            if (! is_string($v) && ! is_numeric($v)) {
                continue;
            }
            $s = (string) $v;
            if (preg_match('/^[a-f0-9]{64}$/i', $s) && ! $hasSigningScheme && ! $implicitSigning) {
                $out[] = 'Headers JSON contains a 64-character hex X-Partner-Signature (often pasted from a Swagger “curl”). That value expires quickly. Use Signature config with scheme hmac_timestamp_v1 and the partner’s pk_/ps_ credentials instead.';
            }
            if (str_starts_with($s, 'ps_') && ! $hasSigningScheme && ! $implicitSigning) {
                $out[] = 'Headers JSON puts a ps_ value in X-Partner-Signature. On many APIs the live header must be a computed signature, not the raw secret — confirm with the partner or use Signature config (hmac_timestamp_v1).';
            }
        }

        $authType = (string) ($integration->auth_type ?? '');
        if (($integration->refresh_timestamp_header_without_signing ?? false) && $normHeaders !== []) {
            foreach (['X-Partner-Signature', 'x-partner-signature'] as $sigHeader) {
                if (! array_key_exists($sigHeader, $normHeaders)) {
                    continue;
                }
                $sv = $normHeaders[$sigHeader];
                if (! is_string($sv) && ! is_numeric($sv)) {
                    continue;
                }
                $s = (string) $sv;
                if (preg_match('/^[a-f0-9]{64}$/i', $s) && ! $hasSigningScheme && ! $implicitSigning) {
                    $out[] = '“Refresh timestamp header” is on, but X-Partner-Signature looks like a static HMAC digest. Partners that require HMAC need Signature config (hmac_timestamp_v1) so timestamp and signature are regenerated together.';

                    break;
                }
            }
        }

        if ($authType === 'api_key_header' && $normHeaders !== []) {
            $auth = is_array($integration->auth_config_json ?? null) ? $integration->auth_config_json : [];
            $headerName = trim((string) ($auth['header_name'] ?? ''));
            if ($headerName !== '') {
                foreach (array_keys($normHeaders) as $hk) {
                    if (is_string($hk) && strcasecmp(trim($hk), $headerName) === 0) {
                        $out[] = 'Headers JSON repeats the same header as Auth (API key). Remove that key from Headers JSON — keep the key only in Auth config to avoid confusion.';

                        break;
                    }
                }
            }
        }

        if ($hasSigningScheme) {
            $scheme = (string) ($signature['scheme'] ?? '');
            if (in_array($scheme, [
                PartnerIntegrationRequestSigner::SCHEME_HMAC_TIMESTAMP_V1,
                PartnerIntegrationRequestSigner::LEGACY_SCHEME_PARTNER_GH_HMAC_V1,
            ], true)) {
                if (trim((string) ($signature['api_key'] ?? '')) === '' || trim((string) ($signature['hmac_secret'] ?? '')) === '') {
                    $out[] = 'Signature config uses a signing scheme but api_key or hmac_secret is missing.';
                }
            }
        }

        return $out;
    }
}
