<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\ProgrammeBatch;
use Illuminate\Support\Facades\Cache;

class ProgrammeBatchObserver
{
    /**
     * When a ProgrammeBatch's dates change, clear all related
     * remaining_seats cache keys so availability is recalculated.
     */
    public function updated(ProgrammeBatch $batch): void
    {
        if (!$batch->wasChanged(['start_date', 'end_date'])) {
            return;
        }

        // Find all bookings tied to this batch and clear their cache keys
        $bookings = Booking::where('programme_batch_id', $batch->id)->get();

        foreach ($bookings as $booking) {
            $centreId = $booking->centre_id;
            $sessionId = $booking->master_session_id;

            if ($centreId && $sessionId) {
                Cache::forget("remaining_seats:{$centreId}:{$batch->id}:{$sessionId}");
            }
        }
    }
}
