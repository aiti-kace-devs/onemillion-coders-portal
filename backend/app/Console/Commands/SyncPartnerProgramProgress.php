<?php

namespace App\Console\Commands;

use App\Jobs\SyncProgramProgressPageJob;
use Illuminate\Console\Command;

class SyncPartnerProgramProgress extends Command
{
    protected $signature = 'partner:sync-program-progress
        {program_slug=gh-program : Partner program slug}
        {--per-page=100 : Page size to request}
        {--updated-since= : ISO timestamp for incremental sync}';

    protected $description = 'Queue Startocode paginated program progress sync jobs';

    public function handle(): int
    {
        $programSlug = (string) $this->argument('program_slug');
        $perPage = (int) $this->option('per-page');
        $updatedSince = $this->option('updated-since');
        $updatedSince = is_string($updatedSince) && trim($updatedSince) !== '' ? trim($updatedSince) : null;

        SyncProgramProgressPageJob::dispatch(
            programSlug: $programSlug,
            page: 1,
            perPage: $perPage,
            updatedSince: $updatedSince
        );

        $this->info("Queued partner program progress sync for {$programSlug}.");

        return self::SUCCESS;
    }
}
