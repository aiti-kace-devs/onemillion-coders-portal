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
    public function book(User $user, Course $course, ProgrammeBatch $batch, $session, bool $isProtocolBooking = false, ?string $capacityPool = null): ?Booking
    {
        // Respect user's protocol flag regardless of caller-provided flag
        $isProtocolBooking = $isProtocolBooking || (! empty($user->is_protocol));
        $selectedPool = $this->resolveCapacityPool($isProtocolBooking, $capacityPool);

        $isInPerson = $course->isInPersonProgramme();

        if ($isInPerson && $isProtocolBooking && $selectedPool === Booking::CAPACITY_POOL_STANDARD) {
            throw new Exception('Protocol in-person enrollment does not fall back to standard slots. Please choose another available recommendation.');
        }

        // Handle regular (Master Session) courses
        $centreId = $course->centre_id;
        $lockKey = "booking_lock:{$centreId}:{$session->id}";

        // Atomic lock ensures only one booking proceeds per centre+session at a time.
        // This prevents two requests from both reading "1 slot left" and both succeeding.
        return Cache::lock($lockKey, 10)->block(5, function () use ($user, $course, $batch, $session, $centreId, $isProtocolBooking, $selectedPool) {
            return DB::transaction(function () use ($user, $course, $batch, $session, $centreId, $isProtocolBooking, $selectedPool) {
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
                $remaining = $this->computeRemainingSeats($centreId, $batch, $session, $selectedPool, $isProtocolBooking);

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

                 // Remove from waitlist if exists
                AdmissionWaitlist::where('user_id', $user->userId)->delete();

                // Clear the cached seat count so the next read reflects this booking
                $this->clearRemainingSeatsCache($centreId, $batch->id, $session->id);

                $bookingData = [
                    'user_id' => $user->userId,
                    'programme_batch_id' => $batch->id,
                    'centre_id' => $centreId,
                    'course_id' => $course->id,
                    'course_type' => $courseType,
                    'is_protocol' => $isProtocolBooking,
                    'capacity_pool' => $selectedPool,
                    'status' => true,
                    'booked_at' => now(),
                    'user_admission_id' => $admission->id,
                ];

                if ($session instanceof CourseSession) {
                    $bookingData['course_session_id'] = $session->id;
                } else {
                    $bookingData['master_session_id'] = $session->id;
                }

                $booking = Booking::create($bookingData);

                // Decrement the programme batch available slots count so
                // callers that inspect the model in the same transaction
                // see the reduced availability.
                try {
                    $batch->decrement('available_slots');
                } catch (\Exception $e) {
                    // Non-fatal: proceed even if decrement fails for some reason.
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

            try {
                $batch->increment('available_slots');
            } catch (\Exception $e) {
                // Non-fatal: availability is derived from bookings, so keep cancel working even if this mirror column fails.
            }
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

            return $this->computeSeatsFromOccupancy(
                $centre,
                $batch,
                $session,
                $forProtocolBooking ? Booking::CAPACITY_POOL_RESERVED : Booking::CAPACITY_POOL_STANDARD,
                $forProtocolBooking
            );
        });
    }

    private function getRemainingSeatsCacheKey(int $centreId, int $batchId, int $sessionId, bool $forProtocolBooking = false): string
    {
        $mode = $forProtocolBooking ? 'protocol' : 'standard';

        return "remaining_seats:{$centreId}:{$batchId}:{$sessionId}:{$mode}";
    }

    private function clearRemainingSeatsCache(int $centreId, int $batchId, int $sessionId): void
    {
        Cache::forget($this->getRemainingSeatsCacheKey($centreId, $batchId, $sessionId, false));
        Cache::forget($this->getRemainingSeatsCacheKey($centreId, $batchId, $sessionId, true));
    }

    /**
     * Compute remaining seats directly from the DB — no cache.
     *
     * Used inside the atomic lock in book() to guarantee a fresh read that
     * cannot race with another concurrent booking.
     */
    protected function computeRemainingSeats(
        int $centreId,
        ProgrammeBatch $batch,
        $session,
        string $selectedPool = Booking::CAPACITY_POOL_STANDARD,
        bool $isProtocolBooking = false
    ): int
    {
        $centre = Centre::find($centreId);
        if (! $centre) {
            return 0;
        }

        return $this->computeSeatsFromOccupancy($centre, $batch, $session, $selectedPool, $isProtocolBooking);
    }

    /**
     * Shared core: capacity minus peak occupancy.
     */
    private function computeSeatsFromOccupancy(
        Centre $centre,
        ProgrammeBatch $batch,
        $session,
        string $selectedPool = Booking::CAPACITY_POOL_STANDARD,
        bool $isProtocolBooking = false
    ): int
    {
        if ($session instanceof CourseSession) {
            $course = $session->relationLoaded('course')
                ? $session->course
                : $session->course()->with('programme')->first();

            if ($course?->isInPersonProgramme()) {
                $breakdown = $this->courseSessionSeatBreakdown($centre, $batch, $session, $course->programme?->courseType());

                return $selectedPool === Booking::CAPACITY_POOL_RESERVED
                    ? (int) $breakdown['reserved_remaining']
                    : (int) $breakdown['standard_remaining'];
            }

            $masterSession = $session->masterSession;
            if (! $masterSession) {
                return 0;
            }

            $courseType = $masterSession->course_type ?? Programme::COURSE_TYPE_SHORT;
            $masterSessionId = $masterSession->id;
        } else {
            $masterSession = $session;
            $courseType = $masterSession->course_type ?? Programme::COURSE_TYPE_SHORT;
            $masterSessionId = $masterSession->id;
        }

        $breakdown = $this->getRemainingSeatBreakdown($centre->id, $batch->id, $masterSessionId, false, $courseType);

        if ($selectedPool === Booking::CAPACITY_POOL_RESERVED && $isProtocolBooking) {
            return (int) $breakdown['reserved_remaining'];
        }

        return (int) $breakdown['standard_remaining'];
    }

    public function getRemainingSeatsForCourseSession(int $centreId, int $batchId, int $courseSessionId, bool $forProtocolBooking = false): int
    {
        $breakdown = $this->getRemainingSeatBreakdown($centreId, $batchId, $courseSessionId, true);

        return $forProtocolBooking
            ? (int) ($breakdown['reserved_remaining'] ?? 0)
            : (int) ($breakdown['standard_remaining'] ?? 0);
    }

    /**
     * @return array{capacity:int,reserved_capacity:int,standard_capacity:int,reserved_remaining:int,standard_remaining:int}
     */
    public function getRemainingSeatBreakdown(int $centreId, int $batchId, int $sessionId, bool $courseSession = false, ?string $courseTypeOverride = null): array
    {
        $centre = Centre::find($centreId);
        $batch = ProgrammeBatch::find($batchId);

        if (! $centre || ! $batch) {
            return $this->emptySeatBreakdown();
        }

        if ($courseSession) {
            $session = CourseSession::with('course.programme')->find($sessionId);

            if (! $session) {
                return $this->emptySeatBreakdown();
            }

            return $this->courseSessionSeatBreakdown($centre, $batch, $session, $courseTypeOverride);
        }

        $session = MasterSession::find($sessionId);

        if (! $session) {
            return $this->emptySeatBreakdown();
        }

        $courseType = $courseTypeOverride ?? ($session->course_type ?? Programme::COURSE_TYPE_SHORT);
        $capacity = $this->resolveEffectiveCapacity($centre, $courseType, $batch, false);
        $reservedCapacity = $centre->protocolReservedSlotsFor($courseType) ?? 0;

        if ($capacity === null) {
            $capacity = self::UNCONFIGURED_ONLINE_CAPACITY_FALLBACK;
        } elseif ($capacity <= 0) {
            return $this->emptySeatBreakdown();
        }

        $totalCapacity = (int) $capacity + max(0, (int) $reservedCapacity);
        $reservedCapacity = min(max(0, (int) $reservedCapacity), $totalCapacity);
        $standardCapacity = max(0, $totalCapacity - $reservedCapacity);

        $rows = DB::table('daily_session_occupancy')
            ->where('centre_id', $centre->id)
            ->where('master_session_id', $session->id)
            ->whereBetween('date', [
                $batch->start_date->toDateString(),
                $batch->end_date->toDateString(),
            ])
            ->get(['occupied_count', 'protocol_occupied_count']);

        $reservedOccupied = 0;
        $standardOccupied = 0;

        foreach ($rows as $row) {
            $total = max(0, (int) ($row->occupied_count ?? 0));
            $protocol = min(max(0, (int) ($row->protocol_occupied_count ?? 0)), $total);
            $standard = max(0, $total - min($protocol, $reservedCapacity));

            $reservedOccupied = max($reservedOccupied, $protocol);
            $standardOccupied = max($standardOccupied, $standard);
        }

        return [
            'capacity' => $totalCapacity,
            'reserved_capacity' => $reservedCapacity,
            'standard_capacity' => $standardCapacity,
            'reserved_remaining' => max(0, $reservedCapacity - $reservedOccupied),
            'standard_remaining' => max(0, $standardCapacity - $standardOccupied),
        ];
    }

    /**
     * @return array{capacity:int,reserved_capacity:int,standard_capacity:int,reserved_remaining:int,standard_remaining:int}
     */
    private function courseSessionSeatBreakdown(Centre $centre, ProgrammeBatch $batch, CourseSession $session, ?string $courseTypeOverride = null): array
    {
        $course = $session->relationLoaded('course')
            ? $session->course
            : $session->course()->with('programme')->first();

        $courseType = $courseTypeOverride
            ?? $course?->programme?->courseType()
            ?? Booking::resolveCourseType((int) $session->course_id);

        $capacity = max(0, (int) ($session->limit ?? 0));

        if ($capacity <= 0) {
            return $this->emptySeatBreakdown();
        }

        $reservedCapacity = min(
            max(0, (int) ($centre->protocolReservedSlotsFor($courseType) ?? 0)),
            $capacity
        );

        $totalOccupied = Booking::query()
            ->where('programme_batch_id', $batch->id)
            ->where('course_session_id', $session->id)
            ->where('status', true)
            ->count();

        $reservedOccupied = Booking::query()
            ->where('programme_batch_id', $batch->id)
            ->where('course_session_id', $session->id)
            ->where('status', true)
            ->where(function ($query) {
                $query->where('capacity_pool', Booking::CAPACITY_POOL_RESERVED)
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('capacity_pool')
                            ->where('is_protocol', true);
                    });
            })
            ->count();

        $reservedOccupied = min($reservedCapacity, $reservedOccupied, $totalOccupied);
        $standardCapacity = max(0, $capacity - $reservedCapacity);
        $standardOccupied = max(0, $totalOccupied - $reservedOccupied);

        return [
            'capacity' => $capacity,
            'reserved_capacity' => $reservedCapacity,
            'standard_capacity' => $standardCapacity,
            'reserved_remaining' => max(0, $reservedCapacity - $reservedOccupied),
            'standard_remaining' => max(0, $standardCapacity - $standardOccupied),
        ];
    }

    /**
     * @return array{capacity:int,reserved_capacity:int,standard_capacity:int,reserved_remaining:int,standard_remaining:int}
     */
    private function emptySeatBreakdown(): array
    {
        return [
            'capacity' => 0,
            'reserved_capacity' => 0,
            'standard_capacity' => 0,
            'reserved_remaining' => 0,
            'standard_remaining' => 0,
        ];
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

        $reserved = $centre->protocolReservedSlotsFor($courseType);

        if ($forProtocolBooking) {
            // Protocol bookings consume from the reserved slots only.
            return $reserved !== null ? (int) $reserved : 0;
        }

        if ($reserved !== null && $capacity !== null) {
            return max(0, $capacity - $reserved);
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
                $standardCapacity = $this->resolveEffectiveCapacity($centre, $courseType, $batch, false);
                $reservedCapacity = max(0, (int) ($centre->protocolReservedSlotsFor($courseType) ?? 0));

                if ($standardCapacity === null) {
                    $standardCapacity = self::UNCONFIGURED_ONLINE_CAPACITY_FALLBACK;
                } elseif ($standardCapacity < 0) {
                    $standardCapacity = 0;
                }

                $sessionOccupancy = $occupancyData->get($sessionId, collect());
                $rows = $sessionOccupancy
                    ->filter(function ($row) use ($batch) {
                        $date = Carbon::parse($row->date);

                        return $date->between($batch->start_date, $batch->end_date);
                    });

                if ($forProtocolBooking) {
                    $protocolOccupied = $rows->max(function ($row) {
                        return max(0, (int) ($row->protocol_occupied_count ?? 0));
                    }) ?? 0;
                    $remaining = max(0, $reservedCapacity - $protocolOccupied);
                } else {
                    $standardOccupied = $rows->max(function ($row) use ($reservedCapacity) {
                        $total = max(0, (int) ($row->occupied_count ?? 0));
                        $protocol = min(max(0, (int) ($row->protocol_occupied_count ?? 0)), $reservedCapacity);

                        return max(0, $total - $protocol);
                    }) ?? 0;
                    $remaining = max(0, $standardCapacity - $standardOccupied);
                }

                $results[$key] = $remaining;
                Cache::put($cacheKey, $remaining, now()->addHour());
            }
        }

        return $results;
    }

    private function resolveCapacityPool(bool $isProtocolBooking, ?string $capacityPool = null): string
    {
        $requestedPool = in_array($capacityPool, [
            Booking::CAPACITY_POOL_RESERVED,
            Booking::CAPACITY_POOL_STANDARD,
        ], true) ? $capacityPool : null;

        if (! $isProtocolBooking) {
            return Booking::CAPACITY_POOL_STANDARD;
        }

        return $requestedPool ?: Booking::CAPACITY_POOL_RESERVED;
    }
}
