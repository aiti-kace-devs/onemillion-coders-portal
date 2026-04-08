<?php

namespace App\Services\Partners;

use App\Models\PartnerIntegration;
use App\Support\PartnerCodeNormalizer;

/**
 * Optional per-request signing driven by {@see PartnerIntegration::$signature_config_json},
 * with optional derivation of {@code hmac_secret} from a "*Signature*" header for configured partner codes.
 *
 * Other partners may rely only on {@see PartnerIntegration::$auth_type}, static {@see PartnerIntegration::$headers_json},
 * or future schemes — nothing here is mandatory for all partners.
 */
final class PartnerIntegrationRequestSigner
{
    /** HMAC-SHA256 over a configurable message; unix timestamp header; common for APIs that document “sign each request”. */
    public const SCHEME_HMAC_TIMESTAMP_V1 = 'hmac_timestamp_v1';

    /**
     * @deprecated Prefer {@see self::SCHEME_HMAC_TIMESTAMP_V1}. Kept for existing saved integrations.
     */
    public const LEGACY_SCHEME_PARTNER_GH_HMAC_V1 = 'partner_gh_hmac_v1';

    /**
     * Merge normalized headers with signed headers when {@code signature_config_json.scheme} is supported
     * (or when a configured partner supplies the secret in a *Signature* header).
     * Replaces any same-named keys from headers_json so stale static values are not sent.
     *
     * @param  array<string, string>  $normalizedHeaders  from PartnerProgressClient::normalizeHeaders
     * @return array<string, string>
     */
    public function mergeSignedHeaders(
        PartnerIntegration $integration,
        string $httpMethod,
        string $endpointPath,
        array $query,
        array $normalizedHeaders
    ): array {
        $derived = $this->findDerivedSignatureSecretCandidate($integration, $normalizedHeaders);

        $base = is_array($integration->signature_config_json) ? $integration->signature_config_json : [];
        if ($derived !== null) {
            $base['hmac_secret'] = $derived['secret'];
            if (trim((string) ($base['signature_header'] ?? '')) === '') {
                $base['signature_header'] = $derived['output_header_name'];
            }
            if (trim((string) ($base['scheme'] ?? '')) === '') {
                $base['scheme'] = self::SCHEME_HMAC_TIMESTAMP_V1;
            }
        }

        if (trim((string) ($base['api_key'] ?? '')) === '') {
            $base['api_key'] = $this->resolveApiKeyFromIntegration($integration, $normalizedHeaders);
        }

        if (trim((string) ($base['scheme'] ?? '')) === ''
            && trim((string) ($base['hmac_secret'] ?? '')) !== ''
            && trim((string) ($base['api_key'] ?? '')) !== '') {
            $base['scheme'] = self::SCHEME_HMAC_TIMESTAMP_V1;
        }

        $scheme = (string) ($base['scheme'] ?? '');
        if ($scheme !== self::SCHEME_HMAC_TIMESTAMP_V1 && $scheme !== self::LEGACY_SCHEME_PARTNER_GH_HMAC_V1) {
            return $normalizedHeaders;
        }

        if (trim((string) ($base['api_key'] ?? '')) === '' || trim((string) ($base['hmac_secret'] ?? '')) === '') {
            return $normalizedHeaders;
        }

        $signed = $this->signHmacTimestampV1($base, $httpMethod, $endpointPath, $query);
        if ($signed === []) {
            return $normalizedHeaders;
        }

        $headers = $normalizedHeaders;
        if ($derived !== null) {
            unset($headers[$derived['header_key']]);
        }
        foreach (array_keys($signed) as $h) {
            unset($headers[$h]);
        }

        return array_merge($headers, $signed);
    }

    /**
     * @return array{secret: string, header_key: string, output_header_name: string}|null
     */
    public function findDerivedSignatureSecretCandidate(PartnerIntegration $integration, array $normalizedHeaders): ?array
    {
        if (! $this->partnerMayDeriveSignatureSecretFromHeader($integration)) {
            return null;
        }

        foreach ($normalizedHeaders as $name => $value) {
            if (! is_string($name)) {
                continue;
            }
            if (! preg_match('/signature/i', $name)) {
                continue;
            }
            if (! is_string($value) && ! is_numeric($value)) {
                continue;
            }
            $sv = trim((string) $value);
            if ($sv === '' || $this->looksLikeHmacHexDigest($sv)) {
                continue;
            }

            return [
                'secret' => $sv,
                'header_key' => $name,
                'output_header_name' => $name,
            ];
        }

        return null;
    }

    /**
     * Normalize Headers JSON (object or Backpack list rows) to a string map for derivation checks.
     *
     * @return array<string, string>
     */
    public function normalizeHeadersFromIntegrationJson(mixed $headersJson): array
    {
        if (! is_array($headersJson)) {
            return [];
        }
        if (array_is_list($headersJson)) {
            $out = [];
            foreach ($headersJson as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $enabled = array_key_exists('enabled', $row) ? (bool) $row['enabled'] : true;
                if (! $enabled) {
                    continue;
                }
                $name = trim((string) ($row['name'] ?? ''));
                $value = (string) ($row['value'] ?? '');
                if ($name !== '') {
                    $out[$name] = $value;
                }
            }

            return $out;
        }
        $out = [];
        foreach ($headersJson as $k => $v) {
            if (is_string($k) && trim($k) !== '' && (is_string($v) || is_numeric($v))) {
                $out[trim($k)] = (string) $v;
            }
        }

        return $out;
    }

    public function partnerMayDeriveSignatureSecretFromHeader(PartnerIntegration $integration): bool
    {
        $code = PartnerCodeNormalizer::normalize((string) $integration->partner_code);
        $allowed = config('services.partner_progress.signature_secret_header_derived_partner_codes', []);
        if (! is_array($allowed)) {
            return false;
        }
        foreach ($allowed as $c) {
            if (PartnerCodeNormalizer::normalize((string) $c) === $code) {
                return true;
            }
        }

        return false;
    }

    private function resolveApiKeyFromIntegration(PartnerIntegration $integration, array $normalizedHeaders): string
    {
        $auth = is_array($integration->auth_config_json ?? null) ? $integration->auth_config_json : [];
        if (($integration->auth_type ?? '') === 'api_key_header') {
            $v = trim((string) ($auth['value'] ?? ''));
            if ($v !== '') {
                return $v;
            }
        }
        foreach ($normalizedHeaders as $name => $value) {
            if (! is_string($name)) {
                continue;
            }
            if (preg_match('/^x-partner-key$/i', $name) && (is_string($value) || is_numeric($value))) {
                return trim((string) $value);
            }
        }

        return '';
    }

    private function looksLikeHmacHexDigest(string $value): bool
    {
        return (bool) preg_match('/^[a-f0-9]{64}$/i', $value);
    }

    /**
     * @param  array<string, mixed>  $cfg
     * @return array<string, string>
     */
    private function signHmacTimestampV1(array $cfg, string $httpMethod, string $endpointPath, array $query): array
    {
        $apiKey = trim((string) ($cfg['api_key'] ?? ''));
        $secret = trim((string) ($cfg['hmac_secret'] ?? ''));
        if ($apiKey === '' || $secret === '') {
            return [];
        }

        $apiKeyHeader = trim((string) ($cfg['api_key_header'] ?? 'X-Partner-Key')) ?: 'X-Partner-Key';
        $timestampHeader = trim((string) ($cfg['timestamp_header'] ?? 'X-Partner-Timestamp')) ?: 'X-Partner-Timestamp';
        $signatureHeader = trim((string) ($cfg['signature_header'] ?? 'X-Partner-Signature')) ?: 'X-Partner-Signature';

        $timestamp = (string) time();
        $messageFormat = (string) ($cfg['message_format'] ?? 'timestamp');
        $encoding = strtolower((string) ($cfg['signature_encoding'] ?? 'hex'));

        $message = match ($messageFormat) {
            'get_path_timestamp' => strtoupper($httpMethod)."\n".$this->normalizePath($endpointPath)."\n".$timestamp,
            'get_path_query_timestamp' => $this->messageGetPathQueryTimestamp($httpMethod, $endpointPath, $query, $timestamp),
            default => $timestamp,
        };

        $keyMaterial = $secret;
        if (! empty($cfg['strip_ps_prefix']) && str_starts_with($keyMaterial, 'ps_')) {
            $keyMaterial = substr($keyMaterial, 3);
        }

        $raw = hash_hmac('sha256', $message, $keyMaterial, true);
        $signature = $encoding === 'base64'
            ? base64_encode($raw)
            : bin2hex($raw);

        return [
            $apiKeyHeader => $apiKey,
            $timestampHeader => $timestamp,
            $signatureHeader => $signature,
        ];
    }

    private function normalizePath(string $path): string
    {
        $path = '/'.ltrim($path, '/');

        return $path === '' ? '/' : $path;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function messageGetPathQueryTimestamp(string $httpMethod, string $endpointPath, array $query, string $timestamp): string
    {
        $path = $this->normalizePath($endpointPath);
        if ($query === []) {
            return strtoupper($httpMethod)."\n".$path."\n".$timestamp;
        }
        ksort($query);
        $pairs = [];
        foreach ($query as $k => $v) {
            $pairs[] = rawurlencode((string) $k).'='.rawurlencode((string) $v);
        }
        $qs = implode('&', $pairs);

        return strtoupper($httpMethod)."\n".$path.'?'.$qs."\n".$timestamp;
    }
}
