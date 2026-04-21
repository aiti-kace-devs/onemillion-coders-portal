<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\UserAdmission;
use App\Services\AvailabilityService;
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

        if ($admission) {
            $admission->update([
                'programme_batch_id' => $booking->programme_batch_id,
                'course_id' => $booking->course_id,
            ]);

            if (! $booking->user_admission_id) {
                $booking->user_admission_id = $admission->id;
                $booking->saveQuietly();
            }
        }

        if ($booking->status && $booking->master_session_id) {
            $this->syncOccupancy($booking, 1);
        }

        $this->clearOccupancyCache($booking);
    }

    public function updated(Booking $booking): void
    {
        if (! $booking->wasChanged('status') || ! $booking->master_session_id) {
            $this->clearOccupancyCache($booking);

            return;
        }

        $wasConfirmed = (bool) $booking->getOriginal('status');
        $isConfirmed = (bool) $booking->status;

        if ($wasConfirmed === $isConfirmed) {
            return;
        }

        $this->syncOccupancy($booking, $isConfirmed ? 1 : -1);
        $this->clearOccupancyCache($booking);
    }

    /**
     * When a booking is deleted, decrement occupancy and clear cache.
     */
    public function deleted(Booking $booking): void
    {
        if ($booking->status && $booking->master_session_id) {
            $this->syncOccupancy($booking, -1);
        }

        $this->clearOccupancyCache($booking);
    }

    /**
     * Iterate through each day of the booking's batch date range and
     * increment/decrement the daily_session_occupancy summary table.
     */
    protected function syncOccupancy(Booking $booking, int $change): void
    {
        $batch = $booking->programmeBatch;
        if (! $batch || ! $batch->start_date || ! $batch->end_date) {
            return;
        }

        $centreId = $booking->centre_id;
        $sessionId = $booking->master_session_id;
        $courseType = $booking->course_type;

        if (! $centreId || ! $sessionId || ! $courseType) {
            return;
        }

        $period = CarbonPeriod::create($batch->start_date, $batch->end_date);

        foreach ($period as $date) {
            $attributes = [
                'date' => $date->toDateString(),
                'centre_id' => $centreId,
                'master_session_id' => $sessionId,
            ];

            $existing = DB::table('daily_session_occupancy')
                ->where($attributes)
                ->first(['occupied_count', 'protocol_occupied_count']);

            if (! $existing) {
                if ($change < 1) {
                    continue;
                }

                DB::table('daily_session_occupancy')->insert($attributes + [
                    'course_type' => $courseType,
                    'occupied_count' => 1,
                    'protocol_occupied_count' => $booking->is_protocol ? 1 : 0,
                ]);

                continue;
            }

            $values = [
                'course_type' => $courseType,
                'occupied_count' => max(0, (int) ($existing->occupied_count ?? 0) + $change),
            ];

            if ($booking->is_protocol) {
                $values['protocol_occupied_count'] = max(0, (int) ($existing->protocol_occupied_count ?? 0) + $change);
            }

            DB::table('daily_session_occupancy')
                ->where($attributes)
                ->update($values);
        }
    }

    /**
     * Clear the cached remaining-seats value for this booking's slot.
     */
    protected function clearOccupancyCache(Booking $booking): void
    {
        $centreId = $booking->centre_id;
        $batchId = $booking->programme_batch_id;
        if (! $centreId || ! $batchId) {
            return;
        }

        if ($booking->master_session_id) {
            Cache::forget("remaining_seats:{$centreId}:{$batchId}:{$booking->master_session_id}:standard");
            Cache::forget("remaining_seats:{$centreId}:{$batchId}:{$booking->master_session_id}:protocol");
        }

        if ($booking->course_session_id) {
            Cache::forget("remaining_seats:course_session:{$centreId}:{$batchId}:{$booking->course_session_id}:standard");
            Cache::forget("remaining_seats:course_session:{$centreId}:{$batchId}:{$booking->course_session_id}:protocol");
        }

        $batch = $booking->programmeBatch;
        if ($batch && $booking->course_id && $batch->start_date && $batch->end_date) {
            AvailabilityService::clearCache(
                (int) $centreId,
                (int) $booking->course_id,
                $batch->start_date,
                $batch->end_date
            );
        }
    }
}
