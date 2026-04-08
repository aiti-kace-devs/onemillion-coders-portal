<?php

namespace App\Observers;

use App\Jobs\SyncProgramProgressPageJob;
use App\Models\PartnerIntegration;
use App\Services\Partners\PartnerRegistry;
use App\Support\PartnerProgramSettings;
use Illuminate\Support\Facades\Schema;

class PartnerIntegrationObserver
{
    public function __construct(
        private readonly PartnerRegistry $partners
    ) {
    }

    public function saved(PartnerIntegration $integration): void
    {
        if (!Schema::hasTable('partner_integrations')) {
            return;
        }

        if (!$integration->is_enabled) {
            return;
        }

        if (trim((string) $integration->base_url) === '') {
            return;
        }

        if (!$this->partners->has((string) $integration->partner_code)) {
            return;
        }

        $partnerCode = (string) $integration->partner_code;
        $programSlug = PartnerProgramSettings::programSlugForPartner($partnerCode);
        $perPage = (int) config('services.partner_progress.bulk_per_page', 100);

        SyncProgramProgressPageJob::dispatch(
            partnerCode: $partnerCode,
            programSlug: $programSlug,
            page: 1,
            perPage: max(1, min($perPage, 100)),
            updatedSince: null
        );
    }
}
