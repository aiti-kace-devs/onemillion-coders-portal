<?php

namespace App\Console\Commands;

use App\Jobs\SyncProgramProgressPageJob;
use App\Services\Partners\PartnerRegistry;
use App\Support\PartnerProgramSettings;
use Illuminate\Console\Command;

class SyncPartnerProgramProgress extends Command
{
    protected $signature = 'partner:sync-program-progress
        {partner_code? : Partner code (defaults to first registered driver)}
        {program_slug? : Program slug (defaults from PARTNER_PROGRESS_* / slugs map)}
        {--per-page=100 : Page size to request}
        {--updated-since= : ISO timestamp for incremental sync}';

    protected $description = 'Queue partner paginated program progress sync jobs';

    public function handle(PartnerRegistry $registry): int
    {
        $partnerCodeArg = $this->argument('partner_code');
        $partnerCode = is_string($partnerCodeArg) && trim($partnerCodeArg) !== ''
            ? trim($partnerCodeArg)
            : null;

        if ($partnerCode === null) {
            $codes = array_keys($registry->all());
            if ($codes === []) {
                $this->error('No partner progress drivers registered.');

                return self::FAILURE;
            }
            $partnerCode = $codes[0];
        }

        $programSlugArg = $this->argument('program_slug');
        $programSlug = is_string($programSlugArg) && trim($programSlugArg) !== ''
            ? trim($programSlugArg)
            : PartnerProgramSettings::programSlugForPartner($partnerCode);

        $perPage = (int) $this->option('per-page');
        $updatedSince = $this->option('updated-since');
        $updatedSince = is_string($updatedSince) && trim($updatedSince) !== '' ? trim($updatedSince) : null;

        if (!$registry->has($partnerCode)) {
            $this->warn("No driver registered for partner_code `{$partnerCode}`; worker will skip this job.");
        }

        SyncProgramProgressPageJob::dispatch(
            partnerCode: $partnerCode,
            programSlug: $programSlug,
            page: 1,
            perPage: $perPage,
            updatedSince: $updatedSince
        );

        $this->info("Queued partner program progress sync for {$partnerCode} / {$programSlug}.");

        return self::SUCCESS;
    }
}
