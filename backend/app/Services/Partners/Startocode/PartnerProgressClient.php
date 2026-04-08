<?php

namespace App\Services\Partners\Startocode;

use App\Models\PartnerIntegration;
use App\Services\Partners\PartnerIntegrationRequestSigner;
use App\Support\PartnerCodeNormalizer;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * HTTP client for the bundled Startocode-style progress API (paths/auth from PartnerIntegration).
 * Other partners should implement {@see \App\Services\Partners\Contracts\PartnerProgressDriver} with their own client;
 * defaults in this class apply only to this adapter, not to every partner in the registry.
 */
class PartnerProgressClient
{
    /**
     * When set, {@see resolveIntegration} returns this row instead of loading from the database.
     * Used only by {@see self::setProbeIntegrationOverride()} for local/live HTTP probes — never set in production requests.
     */
    private static ?PartnerIntegration $integrationProbeOverride = null;

    private const DEFAULT_SINGLE_PROGRESS_PATH = '/api/v2/partners/gh/integration/progress/{omcp_id}';

    private const DEFAULT_BULK_PROGRESS_PATH = '/api/v2/partners/gh/integration/progress/programs/{program_slug}';

    public function fetchStudentProgress(string $partnerCode, string $omcpId, ?CarbonInterface $updatedSince = null): array
    {
        $integration = $this->resolveIntegration($partnerCode);
        $blocked = $this->integrationBlockedResponse($integration, $partnerCode);
        if ($blocked !== null) {
            return $blocked;
        }

        $query = [];
        $queryMap = $this->queryMapFor($integration, 'single_progress');
        if ($updatedSince && $this->shouldSendUpdatedSinceQuery($integration, 'single_progress')) {
            $query[$queryMap['updated_since']] = $updatedSince->toIso8601String();
        }

        $endpoint = $this->buildEndpointPath(
            integration: $integration,
            key: 'single_progress',
            fallbackPath: self::DEFAULT_SINGLE_PROGRESS_PATH,
            replacements: ['{omcp_id}' => rawurlencode($omcpId)],
            context: ['omcp_id' => $omcpId]
        );

        return $this->requestJson(
            endpoint: $endpoint,
            query: $query,
            integration: $integration,
            logContext: ['omcp_id_masked' => $this->maskOmcpId($omcpId)]
        );
    }

    public function fetchProgramProgressPage(
        string $partnerCode,
        string $programSlug,
        int $page = 1,
        int $perPage = 100,
        ?CarbonInterface $updatedSince = null
    ): array {
        $integration = $this->resolveIntegration($partnerCode);
        $blocked = $this->integrationBlockedResponse($integration, $partnerCode);
        if ($blocked !== null) {
            return $blocked;
        }

        $queryMap = $this->queryMapFor($integration, 'bulk_progress');
        $query = [
            $queryMap['page'] => max($page, 1),
            $queryMap['per_page'] => min(max($perPage, 1), 100),
        ];

        if ($updatedSince && $this->shouldSendUpdatedSinceQuery($integration, 'bulk_progress')) {
            $query[$queryMap['updated_since']] = $updatedSince->toIso8601String();
        }

        $endpoint = $this->buildEndpointPath(
            integration: $integration,
            key: 'bulk_progress',
            fallbackPath: self::DEFAULT_BULK_PROGRESS_PATH,
            replacements: ['{program_slug}' => rawurlencode($programSlug)],
            context: ['program_slug' => $programSlug]
        );

        $result = $this->requestJson(
            endpoint: $endpoint,
            query: $query,
            integration: $integration,
            logContext: ['program_slug' => $programSlug, 'page' => (int) ($query[$queryMap['page']] ?? 1)]
        );

        if (!$result['ok']) {
            return $result;
        }

        $payload = is_array($result['payload']) ? $result['payload'] : [];
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $items = $this->extractItems($data);
        $pagination = $this->extractPagination($payload, $data, count($items), $page);

        return [
            ...$result,
            'items' => $items,
            'pagination' => $pagination,
        ];
    }

    private function requestJson(string $endpoint, array $query = [], ?PartnerIntegration $integration = null, array $logContext = []): array
    {
        if ($integration === null) {
            return [
                'ok' => false,
                'status' => 503,
                'retryable' => false,
                'message' => 'Partner integration is not loaded',
                'payload' => null,
            ];
        }

        $baseUrl = rtrim((string) $integration->base_url, '/');
        $timeout = (int) ($integration->timeout_seconds ?? 10);
        $timeout = max(1, min($timeout, 120));
        $retryAttempts = (int) ($integration->retry_attempts ?? 3);
        $retryAttempts = max(1, min($retryAttempts, 10));
        $backoffMs = $this->normalizeBackoffMs($integration->retry_backoff_ms_json ?? null);
        $authType = (string) ($integration->auth_type ?: 'none');
        $authConfig = is_array($integration->auth_config_json ?? null) ? $integration->auth_config_json : [];
        $headersConfig = $this->normalizeHeaders($integration->headers_json ?? null);
        if ($this->shouldRefreshStaticTimestampHeaders($integration)) {
            $headersConfig = $this->applyFreshUnixTimestampHeaders($headersConfig);
        }
        // Optional scheme-specific signing (see PartnerIntegrationRequestSigner); no-op if scheme absent/unknown.
        $headersConfig = app(PartnerIntegrationRequestSigner::class)->mergeSignedHeaders(
            $integration,
            'GET',
            $endpoint,
            $query,
            $headersConfig
        );
        $token = (string) ($authConfig['token'] ?? '');

        if ($baseUrl === '') {
            return [
                'ok' => false,
                'status' => 503,
                'retryable' => false,
                'message' => 'Partner integration base URL is empty (Admin → Partner integrations).',
                'payload' => null,
            ];
        }

        if ($authType === 'bearer_token' && $token === '') {
            return [
                'ok' => false,
                'status' => 503,
                'retryable' => false,
                'message' => 'Bearer token is missing: set `token` in Auth config JSON on the Partner integration.',
                'payload' => null,
            ];
        }

        if ($authType === 'api_key_header') {
            $keyHeaderName = (string) ($authConfig['header_name'] ?? '');
            $keyHeaderValue = (string) ($authConfig['value'] ?? '');
            if ($keyHeaderName === '' || $keyHeaderValue === '') {
                return [
                    'ok' => false,
                    'status' => 503,
                    'retryable' => false,
                    'message' => 'API key header auth is incomplete: set `header_name` and `value` in Auth config JSON.',
                    'payload' => null,
                ];
            }
        }

        if ($authType === 'basic') {
            $username = (string) ($authConfig['username'] ?? '');
            $password = (string) ($authConfig['password'] ?? '');
            if ($username === '' && $password === '') {
                return [
                    'ok' => false,
                    'status' => 503,
                    'retryable' => false,
                    'message' => 'Basic auth is incomplete: set `username` / `password` in Auth config JSON.',
                    'payload' => null,
                ];
            }
        }

        $response = null;
        try {
            for ($attempt = 1; $attempt <= $retryAttempts; $attempt++) {
                $request = Http::acceptJson();
                $request = $request->withHeaders($headersConfig);
                if ($authType === 'bearer_token') {
                    $request = $request->withToken($token);
                } elseif ($authType === 'api_key_header') {
                    $keyHeaderName = (string) ($authConfig['header_name'] ?? '');
                    $keyHeaderValue = (string) ($authConfig['value'] ?? '');
                    // Signing often emits the same API key header; a second withHeaders() uses
                    // array_merge_recursive() and can turn duplicate keys into nested arrays,
                    // corrupting outbound headers and breaking partner signature verification.
                    if ($keyHeaderName !== '' && $keyHeaderValue !== ''
                        && ! $this->headersHasCaseInsensitiveKey($headersConfig, $keyHeaderName)) {
                        $request = $request->withHeaders([$keyHeaderName => $keyHeaderValue]);
                    }
                } elseif ($authType === 'basic') {
                    $username = (string) ($authConfig['username'] ?? '');
                    $password = (string) ($authConfig['password'] ?? '');
                    if ($username !== '' || $password !== '') {
                        $request = $request->withBasicAuth($username, $password);
                    }
                }

                $response = $request
                    ->timeout($timeout)
                    ->get("{$baseUrl}{$endpoint}", $query);

                $status = $response->status();
                $retryable = $status === 429 || ($status >= 500 && $status <= 599);
                if (!$retryable || $attempt === $retryAttempts) {
                    break;
                }

                $delay = $backoffMs[$attempt - 1] ?? end($backoffMs);
                $delay = is_numeric($delay) ? (int) $delay : 200 * $attempt;
                usleep(max(50, $delay) * 1000);
            }
        } catch (Throwable $e) {
            Log::warning('Startocode progress fetch failed', [
                ...$logContext,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'status' => 0,
                'retryable' => true,
                'message' => 'Unable to reach Startocode API',
                'payload' => null,
            ];
        }

        if (!$response) {
            return [
                'ok' => false,
                'status' => 0,
                'retryable' => true,
                'message' => 'No response from Startocode API',
                'payload' => null,
            ];
        }

        $status = $response->status();
        $payload = $response->json();

        if ($response->successful()) {
            return [
                'ok' => true,
                'status' => $status,
                'retryable' => false,
                'message' => (string) ($payload['message'] ?? 'ok'),
                'payload' => $payload,
            ];
        }

        if ($status === 404) {
            return [
                'ok' => false,
                'status' => $status,
                'retryable' => false,
                'message' => (string) ($payload['message'] ?? 'Partner API returned 404'),
                'payload' => $payload,
            ];
        }

        return [
            'ok' => false,
            'status' => $status,
            'retryable' => $status === 429 || ($status >= 500 && $status <= 599),
            'message' => (string) ($payload['message'] ?? 'Partner API error'),
            'payload' => $payload,
        ];
    }

    private function extractItems(array $data): array
    {
        if (is_array($data['items'] ?? null)) {
            return $data['items'];
        }
        if (is_array($data['rows'] ?? null)) {
            return $data['rows'];
        }

        return array_is_list($data) ? $data : [];
    }

    private function extractPagination(array $payload, array $data, int $itemCount, int $requestedPage): array
    {
        $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
        $links = is_array($payload['links'] ?? null) ? $payload['links'] : [];
        $docPagination = is_array($data['pagination'] ?? null) ? $data['pagination'] : [];
        $currentPage = (int) ($meta['current_page'] ?? $data['current_page'] ?? $requestedPage);
        $lastPage = (int) ($meta['last_page'] ?? $data['last_page'] ?? $currentPage);
        $nextPageUrl = $links['next'] ?? $data['next_page_url'] ?? null;

        if (!empty($docPagination)) {
            $currentPage = (int) ($docPagination['page'] ?? $currentPage);
            $lastPage = (int) ($docPagination['last_page'] ?? $lastPage);
        }

        return [
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'has_more' => ($nextPageUrl !== null && $nextPageUrl !== '') || $currentPage < $lastPage,
            'total' => isset($meta['total']) ? (int) $meta['total'] : (isset($docPagination['total']) ? (int) $docPagination['total'] : null),
            'item_count' => $itemCount,
        ];
    }

    private function maskOmcpId(string $omcpId): string
    {
        if (strlen($omcpId) <= 4) {
            return '****';
        }

        return str_repeat('*', max(strlen($omcpId) - 4, 1)) . substr($omcpId, -4);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function integrationBlockedResponse(?PartnerIntegration $integration, string $partnerCode): ?array
    {
        $partnerCode = trim($partnerCode);
        if ($integration === null) {
            return [
                'ok' => false,
                'status' => 503,
                'retryable' => false,
                'message' => "No enabled Partner integration for code `{$partnerCode}`. Use Admin → Partner integrations.",
                'payload' => null,
            ];
        }

        if (trim((string) $integration->base_url) === '') {
            return [
                'ok' => false,
                'status' => 503,
                'retryable' => false,
                'message' => 'Partner integration base URL is empty. Set it in Admin → Partner integrations.',
                'payload' => null,
            ];
        }

        return null;
    }

    /**
     * @return array{updated_since:string,page:string,per_page:string}
     */
    /**
     * When {@see PartnerIntegration::$refresh_timestamp_header_without_signing} is true and no signing {@code scheme}
     * is configured, replace any header whose name contains "Timestamp" (case-insensitive) with the current unix
     * time in seconds. Does not fix HMAC — use {@see PartnerIntegrationRequestSigner} when the partner signs requests.
     */
    private function shouldRefreshStaticTimestampHeaders(PartnerIntegration $integration): bool
    {
        if (! ($integration->refresh_timestamp_header_without_signing ?? false)) {
            return false;
        }
        $sig = $integration->signature_config_json;
        if (is_array($sig) && trim((string) ($sig['scheme'] ?? '')) !== '') {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    private function applyFreshUnixTimestampHeaders(array $headers): array
    {
        $ts = (string) time();
        foreach ($headers as $name => $value) {
            if (! is_string($name)) {
                continue;
            }
            if (preg_match('/timestamp/i', $name)) {
                $headers[$name] = $ts;
            }
        }

        return $headers;
    }

    /**
     * Incremental sync passes {@code updated_since} as a query param (ISO-8601). Some partner APIs do not accept it
     * or return a misleading "invalid timestamp" error for that param — set {@code skip_updated_since: true} on the
     * corresponding {@code endpoints_json} entry to omit it (full fetch each time from OMCP’s perspective).
     */
    private function shouldSendUpdatedSinceQuery(?PartnerIntegration $integration, string $endpointKey): bool
    {
        if ($integration === null) {
            return true;
        }
        $configured = is_array($integration->endpoints_json ?? null) ? $integration->endpoints_json : [];
        $item = $configured[$endpointKey] ?? null;
        if (! is_array($item)) {
            return true;
        }
        if (! array_key_exists('skip_updated_since', $item)) {
            return true;
        }

        return ! (bool) $item['skip_updated_since'];
    }

    private function defaultQueryMap(): array
    {
        return [
            'updated_since' => 'updated_since',
            'page' => 'page',
            'per_page' => 'per_page',
        ];
    }

    /**
     * @internal For {@see partner:probe-http} only when probe override is allowed (local/testing or env flag).
     */
    public static function setProbeIntegrationOverride(?PartnerIntegration $integration): void
    {
        if ($integration !== null && ! self::probeIntegrationOverrideAllowed()) {
            throw new \RuntimeException(
                'Partner integration probe override is disabled. Use APP_ENV=local/testing or set PARTNER_PROGRESS_ALLOW_PROBE_OVERRIDE=true.'
            );
        }
        self::$integrationProbeOverride = $integration;
    }

    private static function probeIntegrationOverrideAllowed(): bool
    {
        if (app()->environment(['local', 'testing'])) {
            return true;
        }

        return (bool) config('services.partner_progress.allow_probe_integration_override', false);
    }

    private function resolveIntegration(string $partnerCode): ?PartnerIntegration
    {
        if (self::$integrationProbeOverride !== null) {
            return self::$integrationProbeOverride;
        }

        $partnerCode = PartnerCodeNormalizer::normalize($partnerCode);
        if ($partnerCode === '' || !Schema::hasTable('partner_integrations')) {
            return null;
        }

        return PartnerIntegration::query()
            ->where('partner_code', $partnerCode)
            ->where('is_enabled', true)
            ->first();
    }

    private function buildEndpointPath(
        ?PartnerIntegration $integration,
        string $key,
        string $fallbackPath,
        array $replacements = [],
        array $context = []
    ): string
    {
        $path = $fallbackPath;
        $configured = is_array($integration?->endpoints_json ?? null) ? $integration->endpoints_json : [];
        $candidate = $configured[$key] ?? null;
        if (is_array($candidate)) {
            $candidatePath = trim((string) ($candidate['path'] ?? ''));
            if ($candidatePath !== '') {
                $path = $candidatePath;
            }
        } elseif (is_string($candidate) && trim($candidate) !== '') {
            $path = trim($candidate);
        }

        foreach ($replacements as $placeholder => $value) {
            $path = str_replace($placeholder, (string) $value, $path);
        }

        $bindingReplacements = $this->resolvePathBindingReplacements($integration, $context);
        foreach ($bindingReplacements as $placeholder => $value) {
            $path = str_replace('{' . $placeholder . '}', rawurlencode((string) $value), $path);
        }

        return str_starts_with($path, '/') ? $path : '/' . $path;
    }

    /**
     * @return array<string,string>
     */
    private function resolvePathBindingReplacements(?PartnerIntegration $integration, array $context): array
    {
        $bindings = is_array($integration?->path_param_bindings_json ?? null) ? $integration->path_param_bindings_json : [];
        if ($bindings === []) {
            return [];
        }

        $allowedTables = config('services.partner_binding_allowed_tables', ['users']);
        if (!is_array($allowedTables) || $allowedTables === []) {
            $allowedTables = ['users'];
        }
        $allowedColumnsMap = config('services.partner_binding_allowed_columns', []);
        if (!is_array($allowedColumnsMap)) {
            $allowedColumnsMap = [];
        }

        $out = [];
        foreach ($bindings as $placeholder => $rule) {
            if (!is_string($placeholder) || trim($placeholder) === '' || !is_array($rule)) {
                continue;
            }

            $source = (string) ($rule['source'] ?? 'context');
            if ($source === 'context') {
                $contextKey = trim((string) ($rule['key'] ?? $placeholder));
                $value = $context[$contextKey] ?? null;
                if ($value !== null && $value !== '') {
                    $out[$placeholder] = (string) $value;
                }
                continue;
            }

            if ($source === 'literal') {
                if (! array_key_exists('value', $rule)) {
                    continue;
                }
                $lit = $rule['value'];
                if (is_bool($lit)) {
                    $out[$placeholder] = $lit ? 'true' : 'false';
                    continue;
                }
                if (is_scalar($lit) && $lit !== '') {
                    $out[$placeholder] = is_string($lit) ? $lit : (string) $lit;
                }
                continue;
            }

            if ($source !== 'db_lookup') {
                continue;
            }

            $table = trim((string) ($rule['table'] ?? ''));
            $column = trim((string) ($rule['column'] ?? ''));
            $whereColumn = trim((string) ($rule['where_column'] ?? 'userId'));
            $whereContextKey = trim((string) ($rule['where_context_key'] ?? 'omcp_id'));
            $whereValue = $context[$whereContextKey] ?? null;
            if ($table === '' || $column === '' || $whereColumn === '' || $whereValue === null || $whereValue === '') {
                continue;
            }
            if (!in_array($table, $allowedTables, true) || !Schema::hasTable($table)) {
                continue;
            }
            $tableAllowedColumns = $allowedColumnsMap[$table] ?? null;
            if (is_array($tableAllowedColumns) && $tableAllowedColumns !== []) {
                if (!in_array($column, $tableAllowedColumns, true) || !in_array($whereColumn, $tableAllowedColumns, true)) {
                    continue;
                }
            }
            if (!Schema::hasColumn($table, $column) || !Schema::hasColumn($table, $whereColumn)) {
                continue;
            }

            try {
                $resolved = DB::table($table)->where($whereColumn, (string) $whereValue)->value($column);
                if ($resolved !== null && $resolved !== '') {
                    $out[$placeholder] = (string) $resolved;
                }
            } catch (Throwable $e) {
                Log::warning('Unable to resolve partner path binding', [
                    'table' => $table,
                    'column' => $column,
                    'where_column' => $whereColumn,
                    'where_context_key' => $whereContextKey,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $out;
    }

    /**
     * @return array{updated_since:string,page:string,per_page:string}
     */
    private function queryMapFor(?PartnerIntegration $integration, string $key): array
    {
        $defaults = $this->defaultQueryMap();
        $configured = is_array($integration?->endpoints_json ?? null) ? $integration->endpoints_json : [];
        $item = $configured[$key] ?? null;
        if (!is_array($item) || !is_array($item['query_map'] ?? null)) {
            return $defaults;
        }

        $queryMap = $item['query_map'];
        return [
            'updated_since' => trim((string) ($queryMap['updated_since'] ?? $defaults['updated_since'])) ?: $defaults['updated_since'],
            'page' => trim((string) ($queryMap['page'] ?? $defaults['page'])) ?: $defaults['page'],
            'per_page' => trim((string) ($queryMap['per_page'] ?? $defaults['per_page'])) ?: $defaults['per_page'],
        ];
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function headersHasCaseInsensitiveKey(array $headers, string $name): bool
    {
        $name = strtolower(trim($name));
        if ($name === '') {
            return false;
        }
        foreach (array_keys($headers) as $k) {
            if (is_string($k) && strtolower($k) === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string,string>
     */
    private function normalizeHeaders(mixed $headersJson): array
    {
        if (!is_array($headersJson)) {
            return [];
        }

        // Accept object map format: {"X-Key":"v"}
        if (array_is_list($headersJson) === false) {
            $out = [];
            foreach ($headersJson as $k => $v) {
                if (is_string($k) && trim($k) !== '' && (is_string($v) || is_numeric($v))) {
                    $out[trim($k)] = (string) $v;
                }
            }
            return $out;
        }

        // Accept list format: [{"name":"X-Key","value":"v","enabled":true}]
        $out = [];
        foreach ($headersJson as $row) {
            if (!is_array($row)) {
                continue;
            }
            $enabled = array_key_exists('enabled', $row) ? (bool) $row['enabled'] : true;
            if (!$enabled) {
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

    /**
     * @return array<int,int>
     */
    private function normalizeBackoffMs(mixed $backoffJson): array
    {
        if (!is_array($backoffJson)) {
            return [200, 600, 1200];
        }

        $items = collect($backoffJson)
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => max(50, (int) $v))
            ->values()
            ->all();

        return $items !== [] ? $items : [200, 600, 1200];
    }
}
