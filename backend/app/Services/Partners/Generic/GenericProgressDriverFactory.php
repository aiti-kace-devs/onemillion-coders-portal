<?php

namespace App\Services\Partners\Generic;

use App\Models\PartnerIntegration;
use App\Services\Partners\Contracts\PartnerProgressDriver;
use App\Services\Partners\Startocode\PartnerProgressClient;
use App\Support\PartnerCodeNormalizer;
use Illuminate\Support\Facades\Schema;

class GenericProgressDriverFactory
{
    public function __construct(
        private readonly PartnerProgressClient $client,
        private readonly ProgressMappingNormalizer $normalizer
    ) {
    }

    public function supports(string $partnerCode): bool
    {
        $code = PartnerCodeNormalizer::normalize($partnerCode);
        if ($code === '' || ! Schema::hasTable('partner_integrations')) {
            return false;
        }

        return PartnerIntegration::query()
            ->where('partner_code', $code)
            ->where('is_enabled', true)
            ->exists();
    }

    /**
     * @return array<int, string>
     */
    public function enabledPartnerCodes(): array
    {
        if (! Schema::hasTable('partner_integrations')) {
            return [];
        }

        return PartnerIntegration::query()
            ->where('is_enabled', true)
            ->orderBy('partner_code')
            ->pluck('partner_code')
            ->map(fn ($c) => PartnerCodeNormalizer::normalize((string) $c))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function make(string $partnerCode): PartnerProgressDriver
    {
        $code = PartnerCodeNormalizer::normalize($partnerCode);
        if ($code === '') {
            throw new \InvalidArgumentException('partner_code cannot be empty.');
        }

        return new GenericProgressDriver($code, $this->client, $this->normalizer);
    }
}
