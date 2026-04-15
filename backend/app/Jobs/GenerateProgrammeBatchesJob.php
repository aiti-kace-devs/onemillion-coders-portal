<?php

namespace App\Jobs;

use App\Models\Batch;
use App\Models\Programme;
use App\Models\Centre;
use App\Services\ProgrammeBatchGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateProgrammeBatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $admissionBatchId,
        public ?int $programmeId = null,
        public ?int $centreId = null
    ) {}

    public function handle(ProgrammeBatchGenerator $generator): void
    {
        $admissionBatch = Batch::find($this->admissionBatchId);
        if (!$admissionBatch) {
            return;
        }

        $programme = $this->programmeId ? Programme::find($this->programmeId) : null;
        $centre = $this->centreId ? Centre::find($this->centreId) : null;

        $generator->generate($admissionBatch, $programme, $centre);
    }
}
