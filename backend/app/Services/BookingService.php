<?php

namespace App\Services;

use App\Events\AdmissionSlotFreed;
use App\Helpers\SchoolDayCalculator;
use App\Http\Controllers\NotificationController;
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
use Illuminate\Support\Facades\Log;

class BookingService
{
    /**
     * When a centre has no slot/seat configuration, online capacity would otherwise
     * collapse to zero. Keep online cohorts available until admins configure limits.
     */
    private const UNCONFIGURED_ONLINE_CAPACITY_FALLBACK = 999999;

    /**
     * Book a user into a programme batch for a specific session.
     *
     * Protocol users consume the reserved pool first. If the UI explicitly offers
     * a standard fallback, the booking must carry that pool intent so cancellation
     * restores the same pool.
     *
     * @throws Exception when capacity is exhausted or the session is incompatible.
     */
    public function book(
        User $user,
        Course $course,
        ProgrammeBatch $batch,
        CourseSession|MasterSession $session,
        bool $isProtocolBooking = false,
        ?string $capacityPool = null
    ): ?Booking {
        $isProtocolBooking = $isProtocolBooking || (! empty($user->is_protocol));

        $centreId = (int) $course->centre_id;
        $sessionLockId = $session instanceof CourseSession && $session->master_session_id
            ? 'master:'.$session->master_session_id
            : ($session instanceof CourseSession ? 'course_session:'.$session->id : 'master:'.$session->id);
        $sessionType = $session instanceof CourseSession ? 'course_session' : 'master_session';
        $lockKey = "booking_lock:{$centreId}:{$sessionLockId}";

        return Cache::lock($lockKey, 10)->block(5, function () use ($user, $course, $batch, $session, $centreId, $sessionType, $isProtocolBooking, $capacityPool) {
            return DB::transaction(function () use ($user, $course, $batch, $session, $centreId, $sessionType, $isProtocolBooking, $capacityPool) {
                $courseType = Booking::resolveCourseType($course->id);

                $existing = Booking::where('user_id', $user->userId)
                    ->where('programme_batch_id', $batch->id)
                    ->where('status', true)
                    ->first();

                if ($existing) {
                    Log::info('Booking already exists', [
                        'user_id' => $user->userId,
                        'batch_id' => $batch->id,
                        'booking_id' => $existing->id,
                    ]);

                    return $existing;
                }

                if ($batch->available_slots !== null && (int) $batch->available_slots <= 0) {
                    Log::warning('Booking failed: programme batch full', [
                        'user_id' => $user->userId,
                        'batch_id' => $batch->id,
                    ]);

                    throw new Exception('No available slots for this programme batch.');
                }

                $poolSelection = $this->resolveBookingPool($course, $batch, $session, $isProtocolBooking, $capacityPool);
                $selectedPool = $poolSelection['pool'];
                $remaining = $poolSelection['remaining'];

                if ($remaining <= 0) {
                    Log::warning('Booking failed: session full', [
                        'centre_id' => $centreId,
                        'batch_id' => $batch->id,
                        'session_id' => $session->id,
                        'session_type' => $sessionType,
                        'for_protocol' => $isProtocolBooking,
                        'capacity_pool' => $selectedPool,
                    ]);

                    throw new Exception($selectedPool === Booking::CAPACITY_POOL_RESERVED
                        ? 'Protocol reserved slots are full for this session.'
                        : 'Standard slots are full for this session.');
                }

                $previous = Booking::where('user_id', $user->userId)
                    ->where('programme_batch_id', '!=', $batch->id)
                    ->where('status', true)
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

                AdmissionWaitlist::where('user_id', $user->userId)->delete();

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
                    'capacity_pool' => $selectedPool,
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

                if ($batch->available_slots !== null) {
                    try {
                        $batch->decrement('available_slots');
                    } catch (Exception $e) {
                        Log::warning('Failed to decrement programme batch slots after booking', [
                            'batch_id' => $batch->id,
                            'booking_id' => $booking->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                NotificationController::notify(
                    $user->id,
                    'COURSE_SELECTION',
                    'Enrollment Confirmed',
                    'You have successfully enrolled in <strong>'.e($course->course_name).'</strong>. You will be notified of next steps.'
                );

                Log::info('Booking created successfully', [
                    'user_id' => $user->userId,
                    'course_id' => $course->id,
                    'batch_id' => $batch->id,
                    'session_id' => $session->id,
                    'session_type' => $sessionType,
                    'centre_id' => $centreId,
                    'for_protocol' => $isProtocolBooking,
                    'capacity_pool' => $selectedPool,
                    'booking_id' => $booking->id,
                ]);

                return $booking;
            });
        });
    }

    /**
     * @return array{pool: string, remaining: int}
     */
    private function resolveBookingPool(
        Course $course,
        ProgrammeBatch $batch,
        CourseSession|MasterSession $session,
        bool $isProtocolBooking,
        ?string $requestedPool
    ): array {
        $centre = Centre::find((int) $course->centre_id);
        if (! $centre) {
            throw new Exception('Centre not found for this course.');
        }

        $requestedPool = in_array($requestedPool, [
            Booking::CAPACITY_POOL_RESERVED,
            Booking::CAPACITY_POOL_STANDARD,
        ], true) ? $requestedPool : null;

        if (! $isProtocolBooking && $requestedPool === Booking::CAPACITY_POOL_RESERVED) {
            throw new Exception('Reserved slots are only available for protocol bookings.');
        }

        $pool = $isProtocolBooking
            ? ($requestedPool ?: Booking::CAPACITY_POOL_RESERVED)
            : Booking::CAPACITY_POOL_STANDARD;

        if ($pool === Booking::CAPACITY_POOL_STANDARD && $isProtocolBooking && $this->hasReservedSeatForCourseBatch($centre, $course, $batch)) {
            throw new Exception('Protocol reserved slots are still available. Please use a reserved slot first.');
        }

        $breakdown = $this->computeSeatBreakdownFromOccupancy($centre, $batch, $session, $course->programme?->courseType());
        $remaining = $pool === Booking::CAPACITY_POOL_RESERVED
            ? (int) $breakdown['reserved_remaining']
            : (int) $breakdown['standard_remaining'];

        return [
            'pool' => $pool,
            'remaining' => $remaining,
        ];
    }

    private function hasReservedSeatForCourseBatch(Centre $centre, Course $course, ProgrammeBatch $batch): bool
    {
        $sessions = $this->capacitySessionsForCourse($course);

        foreach ($sessions as $session) {
            $breakdown = $this->computeSeatBreakdownFromOccupancy($centre, $batch, $session, $course->programme?->courseType());
            if ((int) $breakdown['reserved_remaining'] > 0) {
                return true;
            }
        }

        return false;
    }

    private function capacitySessionsForCourse(Course $course)
    {
        if ($course->isInPersonProgramme()) {
            return $course->activeInPersonEnrollmentSessions();
        }

        $courseType = $course->programme?->courseType() ?? Booking::resolveCourseType($course->id);

        return MasterSession::where('course_type', $courseType)
            ->where('status', true)
            ->where('session_type', '!=', 'Online')
            ->get();
    }

    /**
     * Cancel a booking and restore the consumed capacity.
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
            } catch (Exception $e) {
                Log::warning('Failed to increment programme batch slots after cancellation', [
                    'batch_id' => $batch->id,
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
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

            return $this->remainingForMode(
                $this->computeSeatBreakdownFromOccupancy($centre, $batch, $session),
                $forProtocolBooking
            );
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

            return $this->remainingForMode(
                $this->computeSeatBreakdownFromOccupancy($centre, $batch, $session),
                $forProtocolBooking
            );
        });
    }

    protected function computeRemainingSeats(
        int $centreId,
        ProgrammeBatch $batch,
        CourseSession|MasterSession $session,
        bool $forProtocolBooking = false
    ): int {
        $centre = Centre::find($centreId);
        if (! $centre) {
            return 0;
        }

        return $this->remainingForMode(
            $this->computeSeatBreakdownFromOccupancy($centre, $batch, $session),
            $forProtocolBooking
        );
    }

    /**
     * Shared core: capacity minus peak occupancy, with protocol reserved-pool separation.
     */
    private function computeSeatsFromOccupancy(
        Centre $centre,
        ProgrammeBatch $batch,
        CourseSession|MasterSession $session,
        bool $forProtocolBooking = false
    ): int {
        return $this->remainingForMode(
            $this->computeSeatBreakdownFromOccupancy($centre, $batch, $session),
            $forProtocolBooking
        );
    }

    /**
     * @return array{capacity: int, reserved_capacity: int, standard_capacity: int, reserved_remaining: int, standard_remaining: int}
     */
    public function getRemainingSeatBreakdown(int $centreId, int $batchId, int $sessionId, bool $courseSession = false, ?string $courseType = null): array
    {
        $batch = ProgrammeBatch::find($batchId);
        $centre = Centre::find($centreId);
        $session = $courseSession
            ? CourseSession::with(['course.programme', 'masterSession'])->find($sessionId)
            : MasterSession::find($sessionId);

        if (! $batch || ! $centre || ! $session) {
            return $this->emptySeatBreakdown();
        }

        return $this->computeSeatBreakdownFromOccupancy($centre, $batch, $session, $courseType);
    }

    /**
     * Shared core: capacity minus peak occupancy, separated into reserved and standard pools.
     *
     * @return array{capacity: int, reserved_capacity: int, standard_capacity: int, reserved_remaining: int, standard_remaining: int}
     */
    private function computeSeatBreakdownFromOccupancy(
        Centre $centre,
        ProgrammeBatch $batch,
        CourseSession|MasterSession $session,
        ?string $courseTypeOverride = null
    ): array {
        if ($session instanceof CourseSession) {
            $masterSession = $session->masterSession;
            $courseType = $courseTypeOverride
                ?? $session->course?->programme?->courseType()
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
            return $this->emptySeatBreakdown();
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
            $breakdown = $this->seatBreakdownFromPoolOccupancy((int) $capacity, $reserved, $poolOccupancy);

            if ($session->master_session_id) {
                $masterReserved = $this->protocolReservedCapacity($centre, $courseType, $totalCapacity);
                $masterPoolOccupancy = $this->masterSessionPoolOccupancy($centre, $batch, (int) $session->master_session_id, $masterReserved);
                $masterBreakdown = $this->seatBreakdownFromPoolOccupancy($totalCapacity, $masterReserved, $masterPoolOccupancy);

                return [
                    'capacity' => min($breakdown['capacity'], $masterBreakdown['capacity']),
                    'reserved_capacity' => min($breakdown['reserved_capacity'], $masterBreakdown['reserved_capacity']),
                    'standard_capacity' => min($breakdown['standard_capacity'], $masterBreakdown['standard_capacity']),
                    'reserved_remaining' => min($breakdown['reserved_remaining'], $masterBreakdown['reserved_remaining']),
                    'standard_remaining' => min($breakdown['standard_remaining'], $masterBreakdown['standard_remaining']),
                ];
            }

            return $breakdown;
        }

        $poolOccupancy = $this->masterSessionPoolOccupancy($centre, $batch, (int) $masterSession->id, $reserved);

        return $this->seatBreakdownFromPoolOccupancy((int) $capacity, $reserved, $poolOccupancy);
    }

    protected function resolveEffectiveCapacity(Centre $centre, string $courseType, ProgrammeBatch $batch, bool $forProtocolBooking = false): ?int
    {
        $capacity = $this->resolveTotalCapacity($centre, $courseType, $batch);

        if ($capacity === null) {
            return null;
        }

        $reserved = $this->protocolReservedCapacity($centre, $courseType, (int) $capacity);

        return $forProtocolBooking
            ? $reserved
            : max(0, (int) $capacity - $reserved);
    }

    private function resolveTotalCapacity(Centre $centre, string $courseType, ProgrammeBatch $batch): ?int
    {
        $isShort = $courseType === Programme::COURSE_TYPE_SHORT;
        $capacity = $this->configuredCourseTypeCapacity($centre, $courseType);

        if ($isShort && $this->isIqsActive($batch)) {
            return $capacity ?? ($centre->seat_count ? (int) $centre->seat_count : null);
        }

        return $capacity;
    }

    private function configuredCourseTypeCapacity(Centre $centre, string $courseType): ?int
    {
        $capacity = $centre->slotCapacityFor($courseType);

        if ($capacity === null && $centre->seat_count) {
            $shortPercent = (int) AppConfig::getValue('SHORT_SLOTS_PERCENTAGE', 60);
            $longPercent = (int) AppConfig::getValue('LONG_SLOTS_PERCENTAGE', 40);

            $capacity = $courseType === Programme::COURSE_TYPE_SHORT
                ? (int) round($centre->seat_count * $shortPercent / 100)
                : (int) round($centre->seat_count * $longPercent / 100);
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

        $reservedOccupied = min((int) $reservedOccupied, (int) $totalOccupied);
        $mainOccupied = max(0, (int) $totalOccupied - min($reservedOccupied, $reserved));

        return [
            'protocol_peak' => $reservedOccupied,
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
        return $this->remainingForMode(
            $this->seatBreakdownFromPoolOccupancy($capacity, $reserved, $poolOccupancy),
            $forProtocolBooking
        );
    }

    /**
     * @param  array{protocol_peak: int, main_peak: int}  $poolOccupancy
     * @return array{capacity: int, reserved_capacity: int, standard_capacity: int, reserved_remaining: int, standard_remaining: int}
     */
    private function seatBreakdownFromPoolOccupancy(int $capacity, int $reserved, array $poolOccupancy): array
    {
        $capacity = max(0, $capacity);
        $reserved = min(max(0, $reserved), $capacity);
        $standardCapacity = max(0, $capacity - $reserved);

        return [
            'capacity' => $capacity,
            'reserved_capacity' => $reserved,
            'standard_capacity' => $standardCapacity,
            'reserved_remaining' => max(0, $reserved - (int) $poolOccupancy['protocol_peak']),
            'standard_remaining' => max(0, $standardCapacity - (int) $poolOccupancy['main_peak']),
        ];
    }

    /**
     * @param  array{reserved_remaining: int, standard_remaining: int}  $breakdown
     */
    private function remainingForMode(array $breakdown, bool $forProtocolBooking): int
    {
        return $forProtocolBooking
            ? (int) ($breakdown['reserved_remaining'] ?? 0)
            : (int) ($breakdown['standard_remaining'] ?? 0);
    }

    /**
     * @return array{capacity: int, reserved_capacity: int, standard_capacity: int, reserved_remaining: int, standard_remaining: int}
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

    private function bookingConsumesCentreCapacity(Booking $booking): bool
    {
        return (bool) $booking->status
            && ($booking->master_session_id !== null || $booking->course_session_id !== null);
    }

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

    public function getRemainingSeatsBatch(int $centreId, array $batchIds, array $sessionIds, bool $forProtocolBooking = false): array
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
                $poolOccupancy = $this->poolOccupancyFromRows(
                    $sessionOccupancy->filter(function ($row) use ($batch) {
                        $date = Carbon::parse($row->date);

                        return $date->between($batch->start_date, $batch->end_date);
                    }),
                    $reserved
                );

                $remaining = $this->remainingSeatsFromPoolOccupancy((int) $capacity, $reserved, $poolOccupancy, $forProtocolBooking);
                $results[$key] = $remaining;
                Cache::put($cacheKey, $remaining, now()->addHour());
            }
        }

        return $results;
    }
}
