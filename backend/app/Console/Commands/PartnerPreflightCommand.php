<?php

namespace App\Console\Commands;

use App\Models\PartnerIntegration;
use App\Services\Partners\PartnerIntegrityService;
use App\Support\PartnerCodeNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class PartnerPreflightCommand extends Command
{
    protected $signature = 'partner:preflight {partner_code? : Optional partner code; omit to check all enabled integrations}';

    protected $description = 'Verify partner integration row, course mappings, and resolvable driver for near no-code onboarding';

    public function handle(PartnerIntegrityService $integrity): int
    {
        if (! Schema::hasTable('partner_integrations')) {
            $this->error('partner_integrations table is missing.');

            return self::FAILURE;
        }

        $arg = $this->argument('partner_code');
        $codes = [];
        if (is_string($arg) && trim($arg) !== '') {
            $codes[] = PartnerCodeNormalizer::normalize($arg);
        } else {
            $codes = PartnerIntegration::query()
                ->where('is_enabled', true)
                ->orderBy('partner_code')
                ->pluck('partner_code')
                ->map(fn ($c) => PartnerCodeNormalizer::normalize((string) $c))
                ->filter()
                ->values()
                ->all();
        }

        if ($codes === []) {
            $this->warn('No partner codes to check.');

            return self::SUCCESS;
        }

        $failed = false;
        foreach ($codes as $code) {
            $issues = $integrity->issuesForPartnerCode($code);
            if ($issues === []) {
                $this->info("[{$code}] OK");
                continue;
            }
            $failed = true;
            $this->warn("[{$code}] Issues:");
            foreach ($issues as $issue) {
                $this->line('  - '.$issue);
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
