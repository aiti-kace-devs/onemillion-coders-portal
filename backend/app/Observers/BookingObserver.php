<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\UserAdmission;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BookingObserver
{
    /**
     * Safety net for bookings created outside BookingService (Backpack CRUD, seeders).
     * The service path already sets user_admission_id and updates the admission mirror,
     * so this short-circuits in normal operation.
     */
    public function created(Booking $booking): void
    {
        $admission = UserAdmission::where('user_id', $booking->user_id)->first();
        if (!$admission) {
            return;
        }

        $admission->update([
            'programme_batch_id' => $booking->programme_batch_id,
            'course_id' => $booking->course_id,
        ]);

        $booking->user_admission_id = $admission->id;
        $booking->saveQuietly();

        // Sync occupancy for the new booking
        $this->syncOccupancy($booking, 1);
        $this->clearOccupancyCache($booking);
    }

    /**
     * When a booking is deleted, decrement occupancy and clear cache.
     */
    public function deleted(Booking $booking): void
    {
        $this->syncOccupancy($booking, -1);
        $this->clearOccupancyCache($booking);
    }

    /**
     * Iterate through each day of the booking's batch date range and
     * increment/decrement the daily_session_occupancy summary table.
     */
    protected function syncOccupancy(Booking $booking, int $change): void
    {
        $batch = $booking->programmeBatch;
        if (!$batch || !$batch->start_date || !$batch->end_date) {
            return;
        }

        $centreId = $booking->centre_id;
        $sessionId = $booking->master_session_id;
        $courseType = $booking->course_type;

        if (!$centreId || !$sessionId || !$courseType) {
            return;
        }

        $period = CarbonPeriod::create($batch->start_date, $batch->end_date);

        foreach ($period as $date) {
            DB::table('daily_session_occupancy')->updateOrInsert(
                [
                    'date' => $date->toDateString(),
                    'centre_id' => $centreId,
                    'master_session_id' => $sessionId,
                ],
                [
                    'course_type' => $courseType,
                    'occupied_count' => DB::raw("GREATEST(0, COALESCE(occupied_count, 0) + ({$change}))"),
                ]
            );
        }
    }

    /**
     * Clear the cached remaining-seats value for this booking's slot.
     */
    protected function clearOccupancyCache(Booking $booking): void
    {
        $centreId = $booking->centre_id;
        $batchId = $booking->programme_batch_id;
        $sessionId = $booking->master_session_id;

        if ($centreId && $batchId && $sessionId) {
            Cache::forget("remaining_seats:{$centreId}:{$batchId}:{$sessionId}");
        }
    }
}
