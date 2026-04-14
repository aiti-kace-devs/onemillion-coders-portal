<?php

namespace App\Services;

use App\Models\Centre;
use App\Models\Course;
use App\Models\Programme;
use App\Models\ProgrammeBatch;
use App\Models\Batch;
use App\Models\AppConfig;
use App\Models\MasterSession;
use App\Helpers\SchoolDayCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AvailabilityService
{
    /**
     * Get available slots for a centre/course within a date range.
     *
     * @return array{available: int, quota_source: string, recommendations: array}
     */
    public function getAvailableSlots(int $centreId, int $courseId, Carbon $from, Carbon $to): array
    {
        $cacheKey = "availability:{$centreId}:{$courseId}:{$from->format('Y-m-d')}:{$to->format('Y-m-d')}";
        $ttl = (int) AppConfig::getValue('AVAILABILITY_CACHE_TTL', 300);

        return Cache::remember($cacheKey, $ttl, function () use ($centreId, $courseId, $from, $to) {
            return $this->computeAvailability($centreId, $courseId, $from, $to);
        });
    }

    /**
     * Clear the availability cache for a given key.
     */
    public static function clearCache(int $centreId, int $courseId, Carbon $from, Carbon $to): void
    {
        $cacheKey = "availability:{$centreId}:{$courseId}:{$from->format('Y-m-d')}:{$to->format('Y-m-d')}";
        Cache::forget($cacheKey);
    }

    /**
     * Get detailed occupancy for a centre, broken down by session and date.
     *
     * Returns capacity and booked counts per session per day so the UI can
     * render calendar/slot-picker views.
     *
     * @return array{capacity: int, quota_source: string, sessions: array}
     */
    public function getDetailedOccupancy(int $centreId, string $courseType, Carbon $from, Carbon $to): array
    {
        $centre = Centre::find($centreId);
        if (!$centre) {
            return ['capacity' => 0, 'quota_source' => 'standard', 'sessions' => []];
        }

        // Resolve quota source + effective capacity
        $quotaSource = 'standard';
        $capacity = $this->resolveCapacityForCentre($centre, $courseType, $from, $to, $quotaSource);

        // Fetch all occupancy rows for this centre + date range
        $occupancyRows = DB::table('daily_session_occupancy')
            ->where('centre_id', $centreId)
            ->where('course_type', $courseType)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get();

        // Get all active master sessions for this course type
        $sessions = MasterSession::where('course_type', $courseType)
            ->where('status', true)
            ->get();

        // Build per-session breakdown
        $sessionData = $sessions->map(function ($session) use ($occupancyRows, $capacity) {
            $sessionOccupancy = $occupancyRows
                ->where('master_session_id', $session->id)
                ->keyBy('date');

            $peakOccupied = $sessionOccupancy->max('occupied_count') ?? 0;

            return [
                'session_id' => $session->id,
                'session_name' => $session->master_name,
                'session_time' => $session->time,
                'peak_occupied' => $peakOccupied,
                'capacity' => $capacity,
                'available' => max(0, $capacity - $peakOccupied),
                'daily' => $sessionOccupancy->map(function ($row) use ($capacity) {
                    return [
                        'date' => $row->date,
                        'booked' => $row->occupied_count,
                        'available' => max(0, $capacity - $row->occupied_count),
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();

        return [
            'capacity' => $capacity,
            'quota_source' => $quotaSource,
            'sessions' => $sessionData,
        ];
    }

    private function computeAvailability(int $centreId, int $courseId, Carbon $from, Carbon $to): array
    {
        $course = Course::find($courseId);
        if (!$course || !$course->programme) {
            return ['available' => 0, 'quota_source' => 'standard', 'recommendations' => []];
        }

        $programme = $course->programme;
        $centre = Centre::find($centreId);
        if (!$centre) {
            return ['available' => 0, 'quota_source' => 'standard', 'recommendations' => []];
        }

        $courseType = $programme->courseType();

        // Find the admission batch that covers the [from, to] range
        $admissionBatch = Batch::where('start_date', '<=', $from)
            ->where('end_date', '>=', $to)
            ->where('status', true)
            ->first();

        if (!$admissionBatch) {
            return ['available' => 0, 'quota_source' => 'standard', 'recommendations' => []];
        }

        // Query programme_batches overlapping [from, to]
        $batches = ProgrammeBatch::where('admission_batch_id', $admissionBatch->id)
            ->where('programme_id', $programme->id)
            ->where('status', true)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('start_date', [$from, $to])
                  ->orWhereBetween('end_date', [$from, $to])
                  ->orWhere(function ($q2) use ($from, $to) {
                      $q2->where('start_date', '<=', $from)
                         ->where('end_date', '>=', $to);
                  });
            })
            ->get();

        // Resolve quota source and capacity via IQS
        $quotaSource = 'standard';
        $capacity = $this->resolveCapacityForCentre($centre, $courseType, $from, $to, $quotaSource);

        // Use occupancy table: find peak occupancy across ALL sessions for this course type
        $maxOccupied = DB::table('daily_session_occupancy')
            ->where('centre_id', $centreId)
            ->where('course_type', $courseType)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->max('occupied_count') ?? 0;

        $totalAvailable = max(0, $capacity - $maxOccupied);

        if ($totalAvailable > 0) {
            return [
                'available' => $totalAvailable,
                'quota_source' => $quotaSource,
                'recommendations' => [],
                'batches' => $batches->map(function ($batch) {
                    return [
                        'id' => $batch->id,
                        'start_date' => $batch->start_date->format('Y-m-d'),
                        'end_date' => $batch->end_date->format('Y-m-d'),
                    ];
                })->values()->toArray(),
            ];
        }

        // No availability — build recommendations
        $recommendations = $this->buildRecommendations($centreId, $courseId, $from, $to, $admissionBatch, $centre);

        return [
            'available' => 0,
            'quota_source' => $quotaSource,
            'recommendations' => $recommendations,
            'batches' => [],
        ];
    }

    /**
     * Resolve the effective capacity for a centre/course type,
     * applying the Intelligent Quota System (IQS) when applicable.
     *
     * @param  string  &$quotaSource  Updated by reference to reflect the source.
     */
    private function resolveCapacityForCentre(Centre $centre, string $courseType, Carbon $from, Carbon $to, string &$quotaSource): int
    {
        $isShort = $courseType === Programme::COURSE_TYPE_SHORT;

        // IQS check: is the remaining window too short for any long course?
        if ($isShort && $this->isIqsActive($from, $to)) {
            $quotaSource = 'reallocated';
            return $centre->seat_count ? (int) $centre->seat_count : 0;
        }

        // Standard capacity from centre config
        $capacity = $centre->slotCapacityFor($courseType);

        // If centre hasn't configured slots, derive from global AppConfig
        if ($capacity === null && $centre->seat_count) {
            $shortPercent = (int) AppConfig::getValue('SHORT_SLOTS_PERCENTAGE', 60);
            $longPercent = (int) AppConfig::getValue('LONG_SLOTS_PERCENTAGE', 40);

            $capacity = $isShort
                ? (int) round($centre->seat_count * $shortPercent / 100)
                : (int) round($centre->seat_count * $longPercent / 100);
        }

        return $capacity ?? 0;
    }

    /**
     * Detect whether the Intelligent Quota System should activate.
     *
     * IQS activates when the remaining school days after `$to` in the
     * admission batch are fewer than the smallest long-course duration.
     */
    private function isIqsActive(Carbon $from, Carbon $to): bool
    {
        $admissionBatch = Batch::where('start_date', '<=', $from)
            ->where('end_date', '>=', $to)
            ->where('status', true)
            ->first();

        if (!$admissionBatch || !$admissionBatch->end_date) {
            return false;
        }

        $remainingDays = SchoolDayCalculator::count(
            $to->copy(),
            Carbon::parse($admissionBatch->end_date)
        );

        $smallestLongDuration = Programme::where('time_allocation', Programme::TIME_ALLOCATION_LONG)
            ->whereNotNull('duration_in_days')
            ->min('duration_in_days');

        if (!$smallestLongDuration) {
            return false;
        }

        return $remainingDays < $smallestLongDuration;
    }

    /**
     * Build ordered recommendations when availability == 0.
     */
    private function buildRecommendations(int $centreId, int $courseId, Carbon $from, Carbon $to, ?Batch $admissionBatch, Centre $centre): array
    {
        $recommendations = [];
        $course = Course::find($courseId);

        if (!$course) {
            return $recommendations;
        }

        // (a) Same branch_id, same course, different centre with slots
        if ($centre->branch_id) {
            $siblingCentres = Centre::where('branch_id', $centre->branch_id)
                ->where('id', '!=', $centreId)
                ->get();

            foreach ($siblingCentres as $siblingCentre) {
                $avail = $this->getAvailableSlots($siblingCentre->id, $courseId, $from, $to);
                if ($avail['available'] > 0) {
                    $recommendations[] = [
                        'type' => 'same_branch_centre',
                        'message' => "Available at {$siblingCentre->title}",
                        'centre_id' => $siblingCentre->id,
                        'centre_name' => $siblingCentre->title,
                        'course_id' => $courseId,
                        'batches' => $avail['batches'] ?? [],
                    ];
                }
            }
        }

        // (b) Same centre, different course with slots
        $altCourses = Course::where('centre_id', $centreId)
            ->where('id', '!=', $courseId)
            ->whereHas('programme')
            ->get();

        foreach ($altCourses as $altCourse) {
            $avail = $this->getAvailableSlots($centreId, $altCourse->id, $from, $to);
            if ($avail['available'] > 0) {
                $recommendations[] = [
                    'type' => 'alternative_course',
                    'message' => "{$altCourse->programme->title} has availability",
                    'centre_id' => $centreId,
                    'course_id' => $altCourse->id,
                    'course_name' => $altCourse->programme->title,
                    'batches' => $avail['batches'] ?? [],
                ];
            }
        }

        // (c) No centre support option — admission without booking
        $recommendations[] = [
            'type' => 'no_centre_support',
            'message' => 'Join without centre booking (online/self-study mode)',
        ];

        // (d) Join waitlist
        $recommendations[] = [
            'type' => 'waitlist',
            'message' => 'Join the waitlist to be notified when slots open up',
        ];

        return $recommendations;
    }

    private function countSchoolDays(Carbon $start, Carbon $end): int
    {
        if ($start->gt($end)) {
            return 0;
        }

        return SchoolDayCalculator::count($start, $end);
    }
}
