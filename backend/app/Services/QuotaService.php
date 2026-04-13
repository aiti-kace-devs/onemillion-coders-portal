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
     * When no long-course slots remain at the centre, the full seat_count is returned
     * so short courses can fill the remaining capacity.
     */
    public function getAvailableSlots(Centre $centre, Course $course, string $startDate, string $endDate): int
    {
        $programme = $course->programme;

        $isShortCourse = !$programme
            || ($programme->duration_in_days ?? 0) < \App\Models\Programme::SHORT_COURSE_THRESHOLD_DAYS;

        if ($isShortCourse && !$this->longCourseHasOpenSlots($centre, $startDate, $endDate)) {
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
        return Cache::remember(
            $this->longCourseCacheKey($centre, $startDate, $endDate),
            self::LONG_COURSE_CACHE_TTL,
            function () use ($centre, $startDate, $endDate) {
                return CourseBatch::query()
                    ->whereHas('course', fn($q) => $q->where('centre_id', $centre->id))
                    ->whereHas('course.programme', function ($q) {
                        $q->where('duration_in_days', '>=', \App\Models\Programme::SHORT_COURSE_THRESHOLD_DAYS);
                    })
                    ->overlapping($startDate, $endDate)
                    ->hasAvailableSlots()
                    ->exists();
            }
        );
    }

    /**
     * Flush the long-course slots cache for a given centre and date range.
     */
    public function flushLongCourseCache(Centre $centre, string $startDate, string $endDate): void
    {
        Cache::forget($this->longCourseCacheKey($centre, $startDate, $endDate));
    }

    private function longCourseCacheKey(Centre $centre, string $startDate, string $endDate): string
    {
        return "long_course_slots_{$centre->id}_{$startDate}_{$endDate}";
    }
}
