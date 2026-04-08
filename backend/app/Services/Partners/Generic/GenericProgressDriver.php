<?php

namespace App\Services\Partners\Generic;

use App\Models\PartnerIntegration;
use App\Services\Partners\Contracts\PartnerProgressDriver;
use App\Services\Partners\Startocode\PartnerProgressClient;
use App\Support\PartnerCodeNormalizer;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Schema;

/**
 * Config-driven driver: HTTP via {@see PartnerProgressClient}; normalization via DB mapping + defaults.
 */
class GenericProgressDriver implements PartnerProgressDriver
{
    public function __construct(
        private readonly string $partnerCode,
        private readonly PartnerProgressClient $client,
        private readonly ProgressMappingNormalizer $normalizer
    ) {
    }

    public function code(): string
    {
        return $this->partnerCode;
    }

    public function fetchStudentProgress(string $omcpId, ?CarbonInterface $updatedSince = null): array
    {
        return $this->client->fetchStudentProgress($this->partnerCode, $omcpId, $updatedSince);
    }

    public function fetchProgramProgressPage(
        string $programSlug,
        int $page = 1,
        int $perPage = 100,
        ?CarbonInterface $updatedSince = null
    ): array {
        return $this->client->fetchProgramProgressPage(
            partnerCode: $this->partnerCode,
            programSlug: $programSlug,
            page: $page,
            perPage: $perPage,
            updatedSince: $updatedSince
        );
    }

    public function normalizeSinglePayload(array $payload): array
    {
        $mapping = $this->responseMapping();

        return $this->normalizer->normalizeSinglePayload($payload, $mapping);
    }

    public function normalizeBulkItem(array $item, string $programSlug): array
    {
        $mapping = $this->responseMapping();

        return $this->normalizer->normalizeBulkItem($item, $programSlug, $mapping);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function responseMapping(): ?array
    {
        if (! Schema::hasTable('partner_integrations')) {
            return null;
        }

        $code = PartnerCodeNormalizer::normalize($this->partnerCode);
        $integration = PartnerIntegration::query()->where('partner_code', $code)->first();
        if (! $integration) {
            return null;
        }

        $m = is_array($integration->response_mapping_json ?? null) ? $integration->response_mapping_json : null;

        return $m;
    }
}
