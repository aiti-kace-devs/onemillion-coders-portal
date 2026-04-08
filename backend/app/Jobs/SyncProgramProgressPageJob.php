<?php

namespace App\Jobs;

use App\Services\PartnerProgressSyncService;
use App\Services\Partners\PartnerRegistry;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncProgramProgressPageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 180, 300];

    public function __construct(
        public string $partnerCode,
        public string $programSlug,
        public int $page = 1,
        public int $perPage = 100,
        public ?string $updatedSince = null
    ) {
    }

    /**
     * Protect partner APIs from bursts (important at 999k scale).
     */
    public function middleware(): array
    {
        // Global limiter keyed by partner_code (configured in AppServiceProvider).
        return [
            new RateLimited('partner-progress-bulk'),
        ];
    }

    public function handle(PartnerRegistry $partners, PartnerProgressSyncService $syncService): void
    {
        if (!$partners->has($this->partnerCode)) {
            Log::warning('Program progress page sync skipped (missing partner driver)', [
                'partner_code' => $this->partnerCode,
                'program_slug' => $this->programSlug,
                'page' => $this->page,
            ]);
            return;
        }

        $driver = $partners->get($this->partnerCode);
        $updatedSince = $this->updatedSince ? Carbon::parse($this->updatedSince) : null;
        $result = $driver->fetchProgramProgressPage(
            programSlug: $this->programSlug,
            page: $this->page,
            perPage: $this->perPage,
            updatedSince: $updatedSince
        );

        if (!($result['ok'] ?? false)) {
            Log::warning('Program progress page sync failed', [
                'program_slug' => $this->programSlug,
                'page' => $this->page,
                'status' => $result['status'] ?? 0,
                'message' => $result['message'] ?? 'unknown',
            ]);
            return;
        }

        $items = is_array($result['items'] ?? null) ? $result['items'] : [];
        $counts = ['synced' => 0, 'unresolved' => 0, 'not_eligible' => 0];
        foreach ($items as $item) {
            $syncResult = $syncService->syncBulkItem($this->programSlug, is_array($item) ? $item : [], $this->partnerCode);
            $status = (string) ($syncResult['status'] ?? 'unknown');
            if (array_key_exists($status, $counts)) {
                $counts[$status]++;
            }
        }

        Log::info('Program progress page sync completed', [
            'program_slug' => $this->programSlug,
            'page' => $this->page,
            'fetched' => count($items),
            ...$counts,
        ]);

        $pagination = $result['pagination'] ?? [];
        if (($pagination['has_more'] ?? false) === true) {
            self::dispatch(
                partnerCode: $this->partnerCode,
                programSlug: $this->programSlug,
                page: $this->page + 1,
                perPage: $this->perPage,
                updatedSince: $this->updatedSince
            )->onQueue($this->queue ?? 'default');
        }
    }
}
