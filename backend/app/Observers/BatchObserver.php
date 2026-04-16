<?php

namespace App\Observers;

use App\Models\Batch;
use App\Jobs\GenerateProgrammeBatchesJob;

class BatchObserver
{
    /**
     * Handle the Batch "saved" event.
     */
    public function saved(Batch $batch): void
    {
        // Only regenerate when fields affecting batch generation change
        if (!$batch->wasChanged(['start_date', 'end_date', 'status', 'centre_ids', 'programme_ids'])) {
            return;
        }

        GenerateProgrammeBatchesJob::dispatch($batch->id)->onQueue('default');
    }

    /**
     * Handle the Batch "deleted" event.
     */
    public function deleted(Batch $batch): void
    {
        // Clean up associated programme batches
        $batch->programmeBatches()->delete();
    }
}
