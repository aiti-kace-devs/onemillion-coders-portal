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
    public function book(User $user, Course $course, ProgrammeBatch $batch, $session, bool $isProtocolBooking = false): ?Booking
    {
        // Respect user's protocol flag regardless of caller-provided flag
        $isProtocolBooking = $isProtocolBooking || (! empty($user->is_protocol));

        $centreId = $course->centre_id;
        $sessionLockId = $session instanceof CourseSession && $session->master_session_id
            ? 'master:'.$session->master_session_id
            : ($session instanceof CourseSession ? 'course_session:'.$session->id : 'master:'.$session->id);
        $lockKey = "booking_lock:{$centreId}:{$sessionLockId}";

        // Atomic lock ensures only one booking proceeds per centre+session at a time.
        // This prevents two requests from both reading "1 slot left" and both succeeding.
        return Cache::lock($lockKey, 10)->block(5, function () use ($user, $course, $batch, $session, $centreId, $isProtocolBooking) {
            return DB::transaction(function () use ($user, $course, $batch, $session, $centreId, $isProtocolBooking) {
                $courseType = Booking::resolveCourseType($course->id);

                // Check for existing booking in the same batch first (idempotency).
                // If it already exists, return it and do not try to consume capacity again.
                $existing = Booking::where('user_id', $user->userId)
                    ->where('programme_batch_id', $batch->id)
                    ->first();

                if ($existing) {
                    return $existing;
                }

                if ($batch->available_slots !== null && (int) $batch->available_slots <= 0) {
                    throw new Exception('No available slots for this programme batch.');
                }

                // Bypass the cache and compute remaining seats fresh inside the lock
                $remaining = $this->computeRemainingSeats($centreId, $batch, $session, $isProtocolBooking);

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

                $admissionData = [
                    'course_id' => $course->id,
                    'programme_batch_id' => $batch->id,
                    'email_sent' => now(),
                    'confirmed' => now(),
                    'session' => null,
                ];

                if ($session instanceof CourseSession) {
                    $admissionData['session'] = $session->id;
                }

                $admission = UserAdmission::updateOrCreate(
                    ['user_id' => $user->userId],
                    $admissionData
                );

                 // Remove from waitlist if exists
                AdmissionWaitlist::where('user_id', $user->userId)->delete();

                // Clear the cached seat count so the next read reflects this booking
                if ($session instanceof CourseSession) {
                    $this->clearCourseSessionRemainingSeatsCache($centreId, $batch->id, $session->id);
                    if ($session->master_session_id) {
                        $this->clearRemainingSeatsCache($centreId, $batch->id, $session->master_session_id);
                    }
                } else {
                    $this->clearRemainingSeatsCache($centreId, $batch->id, $session->id);
                }

                $bookingData = [
                    'user_id' => $user->userId,
                    'programme_batch_id' => $batch->id,
                    'centre_id' => $centreId,
                    'course_id' => $course->id,
                    'course_type' => $courseType,
                    'is_protocol' => $isProtocolBooking,
                    'status' => true,
                    'booked_at' => now(),
                    'user_admission_id' => $admission->id,
                ];

                if ($session instanceof CourseSession) {
                    $bookingData['course_session_id'] = $session->id;
                    if ($session->master_session_id) {
                        $bookingData['master_session_id'] = $session->master_session_id;
                    }
                } else {
                    $bookingData['master_session_id'] = $session->id;
                }

                $booking = Booking::create($bookingData);

                // Decrement the programme batch available slots count so
                // callers that inspect the model in the same transaction
                // see the reduced availability.
                if ($batch->available_slots !== null) {
                    try {
                        $batch->decrement('available_slots');
                    } catch (\Exception $e) {
                        // Non-fatal: proceed even if decrement fails for some reason.
                    }
                }

                return $booking;
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

        $slotRestored = $this->bookingConsumesCentreCapacity($booking);
        $daysUntilEnd = now()->diffInDays(Carbon::parse($batch->end_date), false);

        AvailabilityService::clearCache(
            $booking->centre_id,
            $booking->course_id,
            $batch->start_date,
            $batch->end_date
        );

        if ($booking->master_session_id) {
            $this->clearRemainingSeatsCache($booking->centre_id, $batch->id, $booking->master_session_id);
        }

        if ($booking->course_session_id) {
            $this->clearCourseSessionRemainingSeatsCache($booking->centre_id, $batch->id, $booking->course_session_id);
        }

        if ($slotRestored && $batch->available_slots !== null) {
            try {
                $batch->increment('available_slots');
            } catch (\Exception $e) {
                // Non-fatal: capacity is restored by deleting the booking row.
            }
        }

        if ($daysUntilEnd > 7) {
            event(new AdmissionSlotFreed($batch, $booking->userAdmission));
        }

        if ($booking->userAdmission) {
            $booking->userAdmission->update([
                'programme_batch_id' => null,
                'session' => null,
            ]);
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
    public function getRemainingSeats(int $centreId, int $batchId, int $sessionId, bool $forProtocolBooking = false): int
    {
        $cacheKey = $this->getRemainingSeatsCacheKey($centreId, $batchId, $sessionId, $forProtocolBooking);

        return Cache::remember($cacheKey, now()->addHour(), function () use ($centreId, $batchId, $sessionId, $forProtocolBooking) {
            $batch = ProgrammeBatch::find($batchId);
            $centre = Centre::find($centreId);
            $session = MasterSession::find($sessionId);

            if (! $batch || ! $centre || ! $session) {
                return 0;
            }

            return $this->computeSeatsFromOccupancy($centre, $batch, $session, $forProtocolBooking);
        });
    }

    private function getRemainingSeatsCacheKey(int $centreId, int $batchId, int $sessionId, bool $forProtocolBooking = false): string
    {
        $mode = $forProtocolBooking ? 'protocol' : 'standard';

        return "remaining_seats:{$centreId}:{$batchId}:{$sessionId}:{$mode}";
    }

    private function getCourseSessionRemainingSeatsCacheKey(int $centreId, int $batchId, int $courseSessionId, bool $forProtocolBooking = false): string
    {
        $mode = $forProtocolBooking ? 'protocol' : 'standard';

        return "remaining_seats:course_session:{$centreId}:{$batchId}:{$courseSessionId}:{$mode}";
    }

    private function clearRemainingSeatsCache(int $centreId, int $batchId, int $sessionId): void
    {
        Cache::forget($this->getRemainingSeatsCacheKey($centreId, $batchId, $sessionId, false));
        Cache::forget($this->getRemainingSeatsCacheKey($centreId, $batchId, $sessionId, true));
    }

    private function clearCourseSessionRemainingSeatsCache(int $centreId, int $batchId, int $courseSessionId): void
    {
        Cache::forget($this->getCourseSessionRemainingSeatsCacheKey($centreId, $batchId, $courseSessionId, false));
        Cache::forget($this->getCourseSessionRemainingSeatsCacheKey($centreId, $batchId, $courseSessionId, true));
    }

    public function getRemainingSeatsForCourseSession(int $centreId, int $batchId, int $courseSessionId, bool $forProtocolBooking = false): int
    {
        $cacheKey = $this->getCourseSessionRemainingSeatsCacheKey($centreId, $batchId, $courseSessionId, $forProtocolBooking);

        return Cache::remember($cacheKey, now()->addHour(), function () use ($centreId, $batchId, $courseSessionId, $forProtocolBooking) {
            $batch = ProgrammeBatch::find($batchId);
            $centre = Centre::find($centreId);
            $session = CourseSession::with(['course.programme', 'masterSession'])->find($courseSessionId);

            if (! $batch || ! $centre || ! $session) {
                return 0;
            }

            return $this->computeSeatsFromOccupancy($centre, $batch, $session, $forProtocolBooking);
        });
    }

    /**
     * Compute remaining seats directly from the DB — no cache.
     *
     * Used inside the atomic lock in book() to guarantee a fresh read that
     * cannot race with another concurrent booking.
     */
    protected function computeRemainingSeats(int $centreId, ProgrammeBatch $batch, $session, bool $forProtocolBooking = false): int
    {
        $centre = Centre::find($centreId);
        if (! $centre) {
            return 0;
        }

        return $this->computeSeatsFromOccupancy($centre, $batch, $session, $forProtocolBooking);
    }

    /**
     * Shared core: capacity minus peak occupancy.
     */
    private function computeSeatsFromOccupancy(Centre $centre, ProgrammeBatch $batch, $session, bool $forProtocolBooking = false): int
    {
        if ($session instanceof CourseSession) {
            $masterSession = $session->masterSession;
            $courseType = $session->course?->programme?->courseType()
                ?? $masterSession?->course_type
                ?? Programme::COURSE_TYPE_SHORT;
        } else {
            $masterSession = $session;
            $courseType = $masterSession->course_type ?? Programme::COURSE_TYPE_SHORT;
        }

        $capacity = $this->resolveTotalCapacity($centre, $courseType, $batch);

        if ($capacity === null) {
            $capacity = self::UNCONFIGURED_ONLINE_CAPACITY_FALLBACK;
        } elseif ($capacity <= 0) {
            return 0;
        }

        $totalCapacity = (int) $capacity;

        if ($session instanceof CourseSession) {
            $sessionLimit = (int) ($session->limit ?? 0);
            if ($sessionLimit > 0) {
                $capacity = min($totalCapacity, $sessionLimit);
            }
        }

        $reserved = $this->protocolReservedCapacity($centre, $courseType, (int) $capacity);

        if ($session instanceof CourseSession) {
            $poolOccupancy = $this->courseSessionPoolOccupancy($batch, $session, $reserved);
            $remaining = $this->remainingSeatsFromPoolOccupancy((int) $capacity, $reserved, $poolOccupancy, $forProtocolBooking);

            if ($session->master_session_id) {
                $masterReserved = $this->protocolReservedCapacity($centre, $courseType, $totalCapacity);
                $masterPoolOccupancy = $this->masterSessionPoolOccupancy($centre, $batch, (int) $session->master_session_id, $masterReserved);
                $masterRemaining = $this->remainingSeatsFromPoolOccupancy($totalCapacity, $masterReserved, $masterPoolOccupancy, $forProtocolBooking);

                return min($remaining, $masterRemaining);
            }

            return $remaining;
        } else {
            $poolOccupancy = $this->masterSessionPoolOccupancy($centre, $batch, (int) $masterSession->id, $reserved);
        }

        return $this->remainingSeatsFromPoolOccupancy((int) $capacity, $reserved, $poolOccupancy, $forProtocolBooking);
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
    protected function resolveEffectiveCapacity(Centre $centre, string $courseType, ProgrammeBatch $batch, bool $forProtocolBooking = false): ?int
    {
        $capacity = $this->resolveTotalCapacity($centre, $courseType, $batch);

        if ($capacity === null) {
            return null;
        }

        $reserved = $this->protocolReservedCapacity($centre, $courseType, (int) $capacity);

        return $forProtocolBooking
            ? (int) $capacity
            : max(0, (int) $capacity - $reserved);
    }

    private function resolveTotalCapacity(Centre $centre, string $courseType, ProgrammeBatch $batch): ?int
    {
        $isShort = $courseType === Programme::COURSE_TYPE_SHORT;

        // Check IQS: can any more long courses still fit in the admission batch?
        if ($isShort && $this->isIqsActive($batch)) {
            // No more long courses can start — reallocate full capacity to short
            $capacity = $centre->seat_count ? (int) $centre->seat_count : null;
        } else {
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
        }

        return $capacity;
    }

    private function protocolReservedCapacity(Centre $centre, string $courseType, int $capacity): int
    {
        $reserved = $centre->protocolReservedSlotsFor($courseType);

        if ($reserved === null) {
            return 0;
        }

        return min(max(0, (int) $reserved), max(0, $capacity));
    }

    /**
     * @return array{protocol_peak: int, main_peak: int}
     */
    private function masterSessionPoolOccupancy(Centre $centre, ProgrammeBatch $batch, int $masterSessionId, int $reserved): array
    {
        $rows = DB::table('daily_session_occupancy')
            ->where('centre_id', $centre->id)
            ->where('master_session_id', $masterSessionId)
            ->whereBetween('date', [
                $batch->start_date->toDateString(),
                $batch->end_date->toDateString(),
            ])
            ->get(['occupied_count', 'protocol_occupied_count']);

        return $this->poolOccupancyFromRows($rows, $reserved);
    }

    /**
     * @return array{protocol_peak: int, main_peak: int}
     */
    private function courseSessionPoolOccupancy(ProgrammeBatch $batch, CourseSession $session, int $reserved): array
    {
        $totalOccupied = Booking::query()
            ->where('programme_batch_id', $batch->id)
            ->where('course_session_id', $session->id)
            ->where('status', true)
            ->count();

        $protocolOccupied = Booking::query()
            ->where('programme_batch_id', $batch->id)
            ->where('course_session_id', $session->id)
            ->where('status', true)
            ->where('is_protocol', true)
            ->count();

        $protocolOccupied = min((int) $protocolOccupied, (int) $totalOccupied);
        $mainOccupied = max(0, (int) $totalOccupied - min($protocolOccupied, $reserved));

        return [
            'protocol_peak' => $protocolOccupied,
            'main_peak' => $mainOccupied,
        ];
    }

    /**
     * @return array{protocol_peak: int, main_peak: int}
     */
    private function poolOccupancyFromRows($rows, int $reserved): array
    {
        $protocolPeak = 0;
        $mainPeak = 0;

        foreach ($rows as $row) {
            $total = max(0, (int) ($row->occupied_count ?? 0));
            $protocol = min(max(0, (int) ($row->protocol_occupied_count ?? 0)), $total);
            $main = max(0, $total - min($protocol, $reserved));

            $protocolPeak = max($protocolPeak, $protocol);
            $mainPeak = max($mainPeak, $main);
        }

        return [
            'protocol_peak' => $protocolPeak,
            'main_peak' => $mainPeak,
        ];
    }

    /**
     * @param  array{protocol_peak: int, main_peak: int}  $poolOccupancy
     */
    private function remainingSeatsFromPoolOccupancy(int $capacity, int $reserved, array $poolOccupancy, bool $forProtocolBooking): int
    {
        $mainCapacity = max(0, $capacity - $reserved);
        $mainRemaining = max(0, $mainCapacity - (int) $poolOccupancy['main_peak']);

        if (! $forProtocolBooking) {
            return $mainRemaining;
        }

        $reservedRemaining = max(0, $reserved - (int) $poolOccupancy['protocol_peak']);

        return $reservedRemaining + $mainRemaining;
    }

    private function bookingConsumesCentreCapacity(Booking $booking): bool
    {
        return (bool) $booking->status
            && ($booking->master_session_id !== null || $booking->course_session_id !== null);
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
    public function getRemainingSeatsBatch(int $centreId, array $batchIds, array $sessionIds, bool $forProtocolBooking = false): array
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
                $cacheKey = $this->getRemainingSeatsCacheKey($centreId, $batchId, $sessionId, $forProtocolBooking);
                $cached = Cache::get($cacheKey);
                if ($cached !== null) {
                    $results[$key] = (int) $cached;

                    continue;
                }

                $courseType = $session->course_type ?? Programme::COURSE_TYPE_SHORT;
                $capacity = $this->resolveTotalCapacity($centre, $courseType, $batch);

                if ($capacity === null) {
                    $capacity = self::UNCONFIGURED_ONLINE_CAPACITY_FALLBACK;
                } elseif ($capacity <= 0) {
                    $results[$key] = 0;
                    Cache::put($cacheKey, 0, now()->addHour());

                    continue;
                }

                $reserved = $this->protocolReservedCapacity($centre, $courseType, (int) $capacity);
                $sessionOccupancy = $occupancyData->get($sessionId, collect());
                $poolOccupancy = $this->poolOccupancyFromRows($sessionOccupancy
                    ->filter(function ($row) use ($batch) {
                        $date = Carbon::parse($row->date);

                        return $date->between($batch->start_date, $batch->end_date);
                    }), $reserved);

                $remaining = $this->remainingSeatsFromPoolOccupancy((int) $capacity, $reserved, $poolOccupancy, $forProtocolBooking);
                $results[$key] = $remaining;
                Cache::put($cacheKey, $remaining, now()->addHour());
            }
        }

        return $results;
    }
}
