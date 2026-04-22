<?php

namespace App\Services;

use App\Events\AdmissionSlotFreed;
use App\Helpers\SchoolDayCalculator;
use App\Models\AppConfig;
use App\Models\Batch;
use App\Models\Booking;
use App\Models\Centre;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\MasterSession;
use App\Models\Programme;
use App\Models\ProgrammeBatch;
use App\Models\User;
use App\Models\UserAdmission;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingService
{
    private const UNCONFIGURED_ONLINE_CAPACITY_FALLBACK = 999999;

    /**
     * Validate booking availability without creating the booking record.
     *
     * @param CourseSession|MasterSession $session The session to validate (polymorphic)
     * @param bool $isInPerson Whether this is an in-person course
     * @throws Exception when capacity is exhausted or the session is incompatible.
     */
    public function validateBookingAvailability(
        User $user,
        Course $course,
        ProgrammeBatch $batch,
        CourseSession|MasterSession $session,
        bool $isInPerson = false
    ): ?Booking {
        $centreId = $isInPerson ? $course->centre_id : ($session->centre_id ?? $course->centre_id);
        $sessionId = $session->id;
        $sessionType = $isInPerson ? 'course_session' : 'master_session';

        $lockKey = "booking_lock:{$centreId}:{$sessionId}";

        return Cache::lock($lockKey, 10)->block(5, function () use (
            $user, $batch, $centreId, $sessionId, $sessionType, $session, $isInPerson
        ) {
            $existing = Booking::where('user_id', $user->userId)
                ->where('programme_batch_id', $batch->id)
                ->where('status', true)
                ->first();

            if ($existing) {
                Log::info('Booking already exists for batch', [
                    'user_id' => $user->userId,
                    'batch_id' => $batch->id,
                    'booking_id' => $existing->id,
                ]);

                return $existing;
            }

            $remaining = $this->computeRemainingSeats($centreId, $batch, $session, $isInPerson);

            if ($remaining <= 0) {
                Log::warning('Booking validation failed: session full', [
                    'centre_id' => $centreId,
                    'batch_id' => $batch->id,
                    'session_id' => $sessionId,
                    'session_type' => $sessionType,
                ]);

                throw new Exception('Course session is full.');
            }

            return null;
        });
    }

    /**
     * Cancel a booking: hard-delete the row and fire AdmissionSlotFreed
     * so the waitlist is notified (if still > 7 days before batch end).
     */
    public function cancel(Booking $booking): bool
    {
        $batch = $booking->programmeBatch;
        if (! $batch) {
            $booking->delete();
            return false;
        }

        $slotRestored = false;
        $daysUntilEnd = now()->diffInDays(Carbon::parse($batch->end_date), false);

        if ($daysUntilEnd > 7) {
            AvailabilityService::clearCache(
                $booking->centre_id,
                $booking->course_id,
                $batch->start_date,
                $batch->end_date
            );

            $slotRestored = true;
            event(new AdmissionSlotFreed($batch, $booking->userAdmission));
        }

        $booking->delete();
        return $slotRestored;
    }

    /**
     * Get remaining seats for online courses (MasterSession) - cached version
     */
    public function getRemainingSeats(int $centreId, int $batchId, int $sessionId): int
    {
        $cacheKey = "remaining_seats:{$centreId}:{$batchId}:{$sessionId}";

        return Cache::remember($cacheKey, now()->addHour(), function () use ($centreId, $batchId, $sessionId) {
            $batch = ProgrammeBatch::find($batchId);
            $centre = Centre::find($centreId);
            $session = MasterSession::find($sessionId);

            if (! $batch || ! $centre || ! $session) {
                return 0;
            }

            return $this->computeSeatsFromOccupancy($centre, $batch, $session, false);
        });
    }

    /**
     * Compute remaining seats directly from DB — no cache (used inside atomic lock)
     * 
     * @param CourseSession|MasterSession $session
     * @param bool $isInPerson Whether this is an in-person course
     */
    /**
     * Compute remaining seats directly from DB — no cache (used inside atomic lock)
     * 
     * @param CourseSession|MasterSession $session
     * @param bool $isInPerson Whether this is an in-person course
     */
    protected function computeRemainingSeats(
        int $centreId,
        ProgrammeBatch $batch,
        CourseSession|MasterSession $session,
        bool $isInPerson = false
    ): int {
        $centre = Centre::find($centreId);
        if (! $centre) {
            return 0;
        }

        if ($isInPerson && $session instanceof CourseSession) {
            $limit = $session->limit ?? $session->seat_count ?? 0;

            if ($limit <= 0) {
                $limit = $centre->seat_count ?? 0;
            }

            // Count ONLY admissions for this specific programme_batch_id + session + course
            $bookedCount = UserAdmission::where('course_id', $session->course_id)
                ->where('session', $session->id)
                ->where('programme_batch_id', $batch->id)  // Critical: filter by cohort
                ->count();

            return max(0, $limit - $bookedCount);
        }

        //  ONLINE: Keep existing IQS + occupancy logic
        if (! $session instanceof MasterSession) {
            return 0;
        }

        $courseType = $session->course_type ?? Programme::COURSE_TYPE_SHORT;
        $capacity = $this->resolveEffectiveCapacity($centre, $courseType, $batch);

        if ($capacity === null) {
            $capacity = self::UNCONFIGURED_ONLINE_CAPACITY_FALLBACK;
        } elseif ($capacity <= 0) {
            return 0;
        }

        $maxOccupied = DB::table('daily_session_occupancy')
            ->where('centre_id', $centre->id)
            ->where('master_session_id', $session->id)
            ->whereBetween('date', [
                $batch->start_date->toDateString(),
                $batch->end_date->toDateString(),
            ])
            ->max('occupied_count') ?? 0;

        return max(0, $capacity - $maxOccupied);
    }

    /**
     * Shared core: capacity minus current bookings
     * 
     * For in-person (CourseSession): uses session's own capacity/seat_count
     * For online (MasterSession): uses daily_session_occupancy table + IQS logic
     */
    private function computeSeatsFromOccupancy(
        Centre $centre,
        ProgrammeBatch $batch,
        CourseSession|MasterSession $session,
        bool $isInPerson = false
    ): int {
        if ($isInPerson && $session instanceof CourseSession) {
            // IN-PERSON LOGIC: Use CourseSession's own capacity
            $capacity = $session->capacity ?? $session->seat_count ?? 0;

            if ($capacity <= 0) {
                // Fallback to centre seat_count if session has no capacity defined
                $capacity = $centre->seat_count ?? 0;
            }

            // Count confirmed bookings for THIS specific CourseSession + Batch
            $bookedCount = Booking::where('course_session_id', $session->id)
                ->where('programme_batch_id', $batch->id)
                ->where('status', true)
                ->count();

            return max(0, $capacity - $bookedCount);
        }

        // ONLINE LOGIC: Use daily_session_occupancy table + IQS
        if (! $session instanceof MasterSession) {
            return 0;
        }

        $courseType = $session->course_type ?? Programme::COURSE_TYPE_SHORT;
        $capacity = $this->resolveEffectiveCapacity($centre, $courseType, $batch);

        if ($capacity === null) {
            $capacity = self::UNCONFIGURED_ONLINE_CAPACITY_FALLBACK;
        } elseif ($capacity <= 0) {
            return 0;
        }

        $maxOccupied = DB::table('daily_session_occupancy')
            ->where('centre_id', $centre->id)
            ->where('master_session_id', $session->id)
            ->whereBetween('date', [
                $batch->start_date->toDateString(),
                $batch->end_date->toDateString(),
            ])
            ->max('occupied_count') ?? 0;

        return max(0, $capacity - $maxOccupied);
    }

    /**
     * Resolve effective capacity for online courses (IQS logic)
     */
    protected function resolveEffectiveCapacity(Centre $centre, string $courseType, ProgrammeBatch $batch): ?int
    {
        $isShort = $courseType === Programme::COURSE_TYPE_SHORT;

        if ($isShort && $this->isIqsActive($batch)) {
            return $centre->seat_count ? (int) $centre->seat_count : null;
        }

        $capacity = $centre->slotCapacityFor($courseType);

        if ($capacity === null && $centre->seat_count) {
            $shortPercent = (int) AppConfig::getValue('SHORT_SLOTS_PERCENTAGE', 60);
            $longPercent = (int) AppConfig::getValue('LONG_SLOTS_PERCENTAGE', 40);

            $capacity = $isShort
                ? (int) round($centre->seat_count * $shortPercent / 100)
                : (int) round($centre->seat_count * $longPercent / 100);
        }

        return $capacity;
    }

    /**
     * Detect whether IQS should activate
     */
    protected function isIqsActive(ProgrammeBatch $batch): bool
    {
        $admissionBatch = Batch::where('id', $batch->admission_batch_id)
            ->where('status', true)
            ->first();

        if (! $admissionBatch || ! $admissionBatch->end_date) {
            return false;
        }

        $remainingDays = SchoolDayCalculator::count(
            $batch->end_date->copy(),
            Carbon::parse($admissionBatch->end_date)
        );

        $smallestLongDuration = Programme::where('time_allocation', Programme::TIME_ALLOCATION_LONG)
            ->whereNotNull('duration_in_days')
            ->min('duration_in_days');

        if (! $smallestLongDuration) {
            return false;
        }

        return $remainingDays < $smallestLongDuration;
    }

    /**
     * Batch fetch remaining seats for multiple batches/sessions (online courses only)
     */
    public function getRemainingSeatsBatch(int $centreId, array $batchIds, array $sessionIds): array
    {
        if (empty($batchIds) || empty($sessionIds)) {
            return [];
        }

        $centre = Centre::find($centreId);
        if (! $centre) {
            return [];
        }

        $batches = ProgrammeBatch::whereIn('id', $batchIds)->get()->keyBy('id');
        $sessions = MasterSession::whereIn('id', $sessionIds)->get()->keyBy('id');

        $occupancyData = DB::table('daily_session_occupancy')
            ->where('centre_id', $centreId)
            ->whereIn('master_session_id', $sessionIds)
            ->get()
            ->groupBy(function ($row) {
                return $row->master_session_id;
            });

        $results = [];

        foreach ($batchIds as $batchId) {
            $batch = $batches->get($batchId);
            if (! $batch) continue;

            foreach ($sessionIds as $sessionId) {
                $session = $sessions->get($sessionId);
                if (! $session) {
                    $results["{$batchId}:{$sessionId}"] = 0;
                    continue;
                }

                $key = "{$batchId}:{$sessionId}";
                $cacheKey = "remaining_seats:{$centreId}:{$batchId}:{$sessionId}";
                $cached = Cache::get($cacheKey);

                if ($cached !== null) {
                    $results[$key] = (int) $cached;
                    continue;
                }

                $courseType = $session->course_type ?? Programme::COURSE_TYPE_SHORT;
                $capacity = $this->resolveEffectiveCapacity($centre, $courseType, $batch);

                if ($capacity === null) {
                    $capacity = self::UNCONFIGURED_ONLINE_CAPACITY_FALLBACK;
                } elseif ($capacity <= 0) {
                    $results[$key] = 0;
                    Cache::put($cacheKey, 0, now()->addHour());
                    continue;
                }

                $sessionOccupancy = $occupancyData->get($sessionId, collect());
                $maxOccupied = $sessionOccupancy
                    ->filter(function ($row) use ($batch) {
                        $date = Carbon::parse($row->date);
                        return $date->between($batch->start_date, $batch->end_date);
                    })
                    ->max('occupied_count') ?? 0;

                $remaining = max(0, $capacity - $maxOccupied);
                $results[$key] = $remaining;
                Cache::put($cacheKey, $remaining, now()->addHour());
            }
        }

        return $results;
    }
}
