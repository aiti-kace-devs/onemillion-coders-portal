<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\PartnerProgressSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshPartnerProgressJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $backoff = [30, 120, 300];
    public int $tries = 3;

    public function __construct(
        public int $userId,
        public bool $force = false
    ) {
    }

    public function handle(PartnerProgressSyncService $syncService): void
    {
        $user = User::find($this->userId);
        if (!$user) {
            return;
        }

        $syncService->syncUser($user, $this->force);
    }
}
