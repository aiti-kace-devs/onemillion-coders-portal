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
     * Protocol users consume the reserved pool first, then fall back to the main
     * pool when capacity remains. Standard users only see the main pool.
     *
     * @throws Exception when capacity is exhausted or the session is incompatible.
     */
    public function book(
        User $user,
        Course $course,
        ProgrammeBatch $batch,
        CourseSession|MasterSession $session,
        bool $isProtocolBooking = false
    ): ?Booking {
        $isProtocolBooking = $isProtocolBooking || (! empty($user->is_protocol));

        $centreId = (int) $course->centre_id;
        $sessionLockId = $session instanceof CourseSession && $session->master_session_id
            ? 'master:'.$session->master_session_id
            : ($session instanceof CourseSession ? 'course_session:'.$session->id : 'master:'.$session->id);
        $sessionType = $session instanceof CourseSession ? 'course_session' : 'master_session';
        $lockKey = "booking_lock:{$centreId}:{$sessionLockId}";

        return Cache::lock($lockKey, 10)->block(5, function () use ($user, $course, $batch, $session, $centreId, $sessionType, $isProtocolBooking) {
            return DB::transaction(function () use ($user, $course, $batch, $session, $centreId, $sessionType, $isProtocolBooking) {
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

                $remaining = $this->computeRemainingSeats($centreId, $batch, $session, $isProtocolBooking);

                if ($remaining <= 0) {
                    Log::warning('Booking failed: session full', [
                        'centre_id' => $centreId,
                        'batch_id' => $batch->id,
                        'session_id' => $session->id,
                        'session_type' => $sessionType,
                        'for_protocol' => $isProtocolBooking,
                    ]);

                    throw new Exception('Course session is full.');
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
                    'booking_id' => $booking->id,
                ]);

                return $booking;
            });
        });
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

        return $this->computeSeatsFromOccupancy($centre, $batch, $session, $forProtocolBooking);
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
        }

        $poolOccupancy = $this->masterSessionPoolOccupancy($centre, $batch, (int) $masterSession->id, $reserved);

        return $this->remainingSeatsFromPoolOccupancy((int) $capacity, $reserved, $poolOccupancy, $forProtocolBooking);
    }

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
