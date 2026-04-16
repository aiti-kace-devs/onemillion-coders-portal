<?php

namespace App\Observers;

use App\Models\Centre;
use App\Models\Batch;
use App\Jobs\GenerateProgrammeBatchesJob;

class CentreObserver
{
    /**
     * Handle the Centre "saved" event.
     */
    public function saved(Centre $centre): void
    {
        // // Only regenerate batches when capacity-related fields change
        // if (!$centre->wasChanged(['seat_count', 'short_slots_per_day', 'long_slots_per_day'])) {
        //     return;
        // }

        // // Regenerate programme batches for all active admission batches that include this centre
        // // Use integer cast — JSON columns in MySQL store numbers without quotes
        // Batch::where('status', true)
        //     ->whereJsonContains('centre_ids', (int) $centre->id)
        //     ->each(function ($batch) use ($centre) {
        //         GenerateProgrammeBatchesJob::dispatch($batch->id, null, $centre->id)->onQueue('default');
        //     });
    }
}
