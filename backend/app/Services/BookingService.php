<?php

namespace App\Services;

use App\Events\AdmissionSlotFreed;
use App\Helpers\SchoolDayCalculator;
use App\Models\AdmissionWaitlist;
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
use App\Http\Controllers\NotificationController;

class BookingService
{
    /**
     * When a centre has no slot/seat configuration, {@see resolveEffectiveCapacity} returns null.
     * Treating that as zero seats makes every online cohort/session appear full in the UI and
     * blocks bookings; use a high cap until admins configure real limits.
     */
    private const UNCONFIGURED_ONLINE_CAPACITY_FALLBACK = 999999;

    /**
     * Book a user into a programme batch for a specific course session.
     *
     * When {@see Course::isInPersonProgramme} is true and self-paced cohort attachment is false: enforces
     * per-{@see CourseSession} capacity and creates a {@see Booking} with {@see Booking::course_session_id}.
     * For other courses: enforces per-session capacity via daily_session_occupancy summary table
     * and respects the Intelligent Quota System (IQS).
     *
     * @param  bool  $forSelfPacedCohortAttachment  When true (study-from-home cohort only): skip seat-capacity
     *                                              checks and create the booking without occupancy observer side-effects.
     *
     * @throws Exception when capacity is exhausted or the session is incompatible.
     */
    public function book(User $user, Course $course, ProgrammeBatch $batch, $session): ?Booking
    {
        $programme = $course->programme;
        $isInPerson = strtolower(trim((string) $programme->mode_of_delivery)) === 'in person';

        // Handle regular (Master Session) courses
        $centreId = $course->centre_id;
        $lockKey = "booking_lock:{$centreId}:{$session->id}";

        // Atomic lock ensures only one booking proceeds per centre+session at a time.
        // This prevents two requests from both reading "1 slot left" and both succeeding.
        return Cache::lock($lockKey, 10)->block(5, function () use ($user, $course, $batch, $session, $centreId) {
            return DB::transaction(function () use ($user, $course, $batch, $session, $centreId) {
                $courseType = Booking::resolveCourseType($course->id);

                // Check for existing booking in the same batch first (idempotency).
                // If it already exists, return it and do not try to consume capacity again.
                $existing = Booking::where('user_id', $user->userId)
                    ->where('programme_batch_id', $batch->id)
                    ->first();

                if ($existing) {
                    return $existing;
                }

                // Bypass the cache and compute remaining seats fresh inside the lock
                $remaining = $this->computeRemainingSeats($centreId, $batch, $session);

                if ($remaining <= 0) {
                    throw new Exception('Course session is full.');
                }

                // Cancel any previous booking for a different batch
                $previous = Booking::where('user_id', $user->userId)
                    ->where('programme_batch_id', '!=', $batch->id)
                    ->first();

                if ($previous) {
                    $this->cancel($previous);
                }

                AvailabilityService::clearCache(
                    $centreId,
                    $course->id,
                    $batch->start_date,
                    $batch->end_date
                );


                $user->registered_course = $course->id;
                $user->shortlist = true;
                $user->save();
                $admission = UserAdmission::updateOrCreate(
                    ['user_id' => $user->userId],
                    [
                        'course_id' => $course->id,
                        'programme_batch_id' => $batch->id,
                        'email_sent' => now(),
                        'confirmed' => now(),
                        'session' => $session->id,
                    ]
                );

                NotificationController::notify(
                    $user->id,
                    'COURSE_SELECTION',
                    'Enrollment Confirmed',
                    'You have successfully enrolled in <strong>' . e($course->course_name) . '</strong>. You will be notified of next steps.'
                );

                // Remove from waitlist if exists
                AdmissionWaitlist::where('user_id', $user->userId)->delete();

                // Clear the cached seat count so the next read reflects this booking
                Cache::forget("remaining_seats:{$centreId}:{$batch->id}:{$session->id}");

                return Booking::create([
                    'user_id' => $user->userId,
                    'programme_batch_id' => $batch->id,
                    'master_session_id' => $session->id,
                    'centre_id' => $centreId,
                    'course_id' => $course->id,
                    'course_type' => $courseType,
                    'status' => true,
                    'booked_at' => now(),
                    'user_admission_id' => $admission->id,
                ]);
            });
        });
    }


    /**
     * Cancel a booking: hard-delete the row and fire AdmissionSlotFreed
     * so the waitlist is notified (if still > 7 days before batch end).
     *
     * The BookingObserver handles decrementing the occupancy table and
     * clearing the cache on delete.
     *
     * @return bool true if the slot was restored (7-day rule passed).
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
     * Get remaining seats for a specific centre + batch + session combination.
     *
     * Uses the daily_session_occupancy summary table with a MAX() query to find
     * the peak occupancy day, then subtracts from the effective capacity.
     *
     * Respects the Intelligent Quota System (IQS):
     * - If the remaining window in the admission batch is too short for any long
     *   course to fit, short courses get the full centre seat_count as capacity.
     * - Otherwise, capacity comes from centre->slotCapacityFor(courseType), which
     *   falls back to AppConfig global percentages if centre slots are null.
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

            return $this->computeSeatsFromOccupancy($centre, $batch, $session);
        });
    }

    /**
     * Compute remaining seats directly from the DB — no cache.
     *
     * Used inside the atomic lock in book() to guarantee a fresh read that
     * cannot race with another concurrent booking.
     */
    protected function computeRemainingSeats(int $centreId, ProgrammeBatch $batch, MasterSession $session): int
    {
        $centre = Centre::find($centreId);
        if (! $centre) {
            return 0;
        }

        return $this->computeSeatsFromOccupancy($centre, $batch, $session);
    }

    /**
     * Shared core: capacity minus peak occupancy.
     */
    private function computeSeatsFromOccupancy(Centre $centre, ProgrammeBatch $batch, MasterSession $session): int
    {
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
     * Resolve the effective capacity for a course type at a centre,
     * applying the Intelligent Quota System (IQS) when applicable.
     *
     * IQS Logic:
     * - If the remaining window in the admission batch is too short
     *   for the smallest long-course duration, short courses are
     *   reallocated the full centre seat_count.
     * - Otherwise, use the centre's configured slots (or derive from
     *   global AppConfig percentages).
     */
    protected function resolveEffectiveCapacity(Centre $centre, string $courseType, ProgrammeBatch $batch): ?int
    {
        $isShort = $courseType === Programme::COURSE_TYPE_SHORT;

        // Check IQS: can any more long courses still fit in the admission batch?
        if ($isShort && $this->isIqsActive($batch)) {
            // No more long courses can start — reallocate full capacity to short
            return $centre->seat_count ? (int) $centre->seat_count : null;
        }

        // Standard capacity from centre's configured slots
        $capacity = $centre->slotCapacityFor($courseType);

        // If centre hasn't configured slots, derive from global AppConfig
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
     * Detect whether the Intelligent Quota System should activate.
     *
     * IQS activates when the remaining school days in the admission batch
     * (from the programme batch end_date to the admission batch end_date)
     * are fewer than the smallest long-course duration.
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

        // Find the smallest long-course duration
        $smallestLongDuration = Programme::where('time_allocation', Programme::TIME_ALLOCATION_LONG)
            ->whereNotNull('duration_in_days')
            ->min('duration_in_days');

        if (! $smallestLongDuration) {
            return false;
        }

        return $remainingDays < $smallestLongDuration;
    }

    /**
     * Batch fetch remaining seats for multiple batches and sessions at once.
     *
     * This avoids N+1 query problems by loading all data in bulk and
     * computing seats with minimal database queries.
     *
     * @param  int  $centreId  The centre ID
     * @param  array  $batchIds  Array of programme batch IDs
     * @param  array  $sessionIds  Array of master session IDs
     * @return array Map of "batchId:sessionId" => remainingSeats
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

        // Load all batches and sessions at once
        $batches = ProgrammeBatch::whereIn('id', $batchIds)->get()->keyBy('id');
        $sessions = MasterSession::whereIn('id', $sessionIds)->get()->keyBy('id');

        // Fetch all occupancy data in one query
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
            if (! $batch) {
                continue;
            }

            foreach ($sessionIds as $sessionId) {
                $session = $sessions->get($sessionId);
                if (! $session) {
                    $results["{$batchId}:{$sessionId}"] = 0;

                    continue;
                }

                $key = "{$batchId}:{$sessionId}";

                // Try cache first for this specific combination
                $cacheKey = "remaining_seats:{$centreId}:{$batchId}:{$sessionId}";
                $cached = Cache::get($cacheKey);
                if ($cached !== null) {
                    $results[$key] = (int) $cached;

                    continue;
                }

                // Compute and cache (align with computeSeatsFromOccupancy capacity rules)
                $courseType = $session->course_type ?? Programme::COURSE_TYPE_SHORT;
                $capacity = $this->resolveEffectiveCapacity($centre, $courseType, $batch);

                if ($capacity === null) {
                    $capacity = self::UNCONFIGURED_ONLINE_CAPACITY_FALLBACK;
                } elseif ($capacity <= 0) {
                    $results[$key] = 0;
                    Cache::put($cacheKey, 0, now()->addHour());

                    continue;
                }

                // Get max occupancy for this session within the batch date range
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
