<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Batch;
use Illuminate\Support\Collection;

class ProgrammeBatchCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param Batch $admissionBatch
     * @param Collection<int, \App\Models\ProgrammeBatch> $batches
     */
    public function __construct(
        public Batch $admissionBatch,
        public Collection $batches
    ) {}
}
