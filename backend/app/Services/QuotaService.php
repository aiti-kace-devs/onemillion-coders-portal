<?php

namespace App\Services;

use App\Models\Centre;
use App\Models\CourseBatch;
use App\Models\Course;
use Illuminate\Support\Facades\Cache;

class QuotaService
{
    private const LONG_COURSE_CACHE_TTL = 300; // 5 minutes

    /**
     * Get the number of available slots for a given centre, course, and date range.
     *
     * If no long-course slots remain at the centre, the full seat_count is returned
     * so short courses can fill the remaining capacity.
     */
    public function getAvailableSlots(Centre $centre, Course $course, string $startDate, string $endDate): int
    {
        $programme = $course->programme;

        // Short course: duration_in_days < 20 (less than 1 month)
        $isShortCourse = !$programme || ($programme->duration_in_days ?? 0) < 20;

        if ($isShortCourse && !$this->longCourseHasOpenSlots($centre, $startDate, $endDate)) {
            // Expand short-course quota to fill the full seat capacity
            return $centre->seat_count ?? 0;
        }

        return $isShortCourse
            ? $centre->resolvedShortSlotsPerDay()
            : $centre->resolvedLongSlotsPerDay();
    }

    /**
     * Check whether any long-course programme batch still has open slots at this centre
     * during the given date range. Result is cached for 5 minutes.
     */
    public function longCourseHasOpenSlots(Centre $centre, string $startDate, string $endDate): bool
    {
        $cacheKey = "long_course_slots_{$centre->id}_{$startDate}_{$endDate}";

        return Cache::remember($cacheKey, self::LONG_COURSE_CACHE_TTL, function () use ($centre, $startDate, $endDate, &$result) {
            return CourseBatch::query()
                ->whereHas('course', function ($q) use ($centre) {
                    $q->where('centre_id', $centre->id);
                })
                ->whereHas('course.programme', function ($q) {
                    // Long course: duration_in_days >= 20
                    $q->where('duration_in_days', '>=', 20);
                })
                ->where('start_date', '<=', $endDate)
                ->where('end_date', '>=', $startDate)
                ->where('available_slots', '>', 0)
                ->exists();
        });
    }

    /**
     * Flush the long-course slots cache for a given centre and date range.
     */
    public function flushLongCourseCache(Centre $centre, string $startDate, string $endDate): void
    {
        $cacheKey = "long_course_slots_{$centre->id}_{$startDate}_{$endDate}";
        Cache::forget($cacheKey);
    }
}
