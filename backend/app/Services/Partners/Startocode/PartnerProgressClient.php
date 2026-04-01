<?php

namespace App\Services\Partners\Startocode;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PartnerProgressClient
{
    public function fetchStudentProgress(string $omcpId, ?CarbonInterface $updatedSince = null): array
    {
        $query = [];
        if ($updatedSince) {
            $query['updated_since'] = $updatedSince->toIso8601String();
        }

        return $this->requestJson(
            endpoint: "/api/v2/partners/gh/integration/progress/{$omcpId}",
            query: $query,
            logContext: ['omcp_id_masked' => $this->maskOmcpId($omcpId)]
        );
    }

    public function fetchProgramProgressPage(
        string $programSlug,
        int $page = 1,
        int $perPage = 100,
        ?CarbonInterface $updatedSince = null
    ): array {
        $query = [
            'page' => max($page, 1),
            'per_page' => min(max($perPage, 1), 250),
        ];

        if ($updatedSince) {
            $query['updated_since'] = $updatedSince->toIso8601String();
        }

        $result = $this->requestJson(
            endpoint: "/api/v2/partners/gh/integration/progress/programs/{$programSlug}",
            query: $query,
            logContext: ['program_slug' => $programSlug, 'page' => $query['page']]
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

    private function requestJson(string $endpoint, array $query = [], array $logContext = []): array
    {
        $baseUrl = rtrim((string) config('services.partner_startocode.base_url'), '/');
        $token = (string) config('services.partner_startocode.token');
        $timeout = (int) config('services.partner_startocode.timeout_seconds', 10);

        if ($baseUrl === '' || $token === '') {
            return [
                'ok' => false,
                'status' => 500,
                'retryable' => false,
                'message' => 'Startocode API credentials are not configured',
                'payload' => null,
            ];
        }

        $response = null;
        try {
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $response = Http::acceptJson()
                    ->withToken($token)
                    ->timeout($timeout)
                    ->get("{$baseUrl}{$endpoint}", $query);

                $status = $response->status();
                $retryable = $status === 429 || ($status >= 500 && $status <= 599);
                if (!$retryable || $attempt === 3) {
                    break;
                }

                usleep((int) (random_int(200, 600) * $attempt * 1000));
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
        $currentPage = (int) ($meta['current_page'] ?? $data['current_page'] ?? $requestedPage);
        $lastPage = (int) ($meta['last_page'] ?? $data['last_page'] ?? $currentPage);
        $nextPageUrl = $links['next'] ?? $data['next_page_url'] ?? null;

        return [
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'has_more' => ($nextPageUrl !== null && $nextPageUrl !== '') || $currentPage < $lastPage,
            'total' => isset($meta['total']) ? (int) $meta['total'] : null,
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
}
