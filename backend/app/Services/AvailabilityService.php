<?php

namespace App\Services;

use App\Models\Centre;
use App\Models\Course;
use App\Models\Programme;
use App\Models\ProgrammeBatch;
use App\Models\Batch;
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
        $ttl = (int) \App\Models\AppConfig::getValue('AVAILABILITY_CACHE_TTL', 300);

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

        $isShort = $programme->time_allocation == 2;

        // Find the admission batch that covers the [from, to] range
        $admissionBatch = Batch::where('start_date', '<=', $from)
            ->where('end_date', '>=', $to)
            ->where('status', true)
            ->first();

        if (!$admissionBatch) {
            return ['available' => 0, 'quota_source' => 'standard', 'recommendations' => []];
        }

        // Query programme_batches for this centre/programme overlapping [from, to]
        $batches = ProgrammeBatch::where('admission_batch_id', $admissionBatch->id)
            ->where('programme_id', $programme->id)
            ->where('centre_id', $centreId)
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

        // Dynamic reallocation check: if long course can't fit remaining window, short courses use full capacity
        $quotaSource = 'standard';
        $remainingWindowDays = SchoolDayCalculator::count($to->copy(), Carbon::parse($admissionBatch->end_date));

        // Find smallest long-course duration_in_days across all programmes
        $smallestLongDuration = Programme::where('time_allocation', 4)
            ->whereNotNull('duration_in_days')
            ->min('duration_in_days');

        if ($isShort && $smallestLongDuration && $remainingWindowDays < $smallestLongDuration) {
            // No more long cycle fits — reallocate full centre capacity for short courses.
            // Calculate: centre seat_count minus currently enrolled students across all batches.
            $quotaSource = 'reallocated';
            $totalEnrolled = $batches->sum(function ($batch) {
                return max(0, $batch->max_enrolments - $batch->available_slots);
            });
            $totalAvailable = max(0, $centre->seat_count - $totalEnrolled);
        } else {
            $totalAvailable = $batches->sum('available_slots');
        }

        $totalAvailable = max(0, (int) $totalAvailable);

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
                        'available_slots' => $batch->available_slots,
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
