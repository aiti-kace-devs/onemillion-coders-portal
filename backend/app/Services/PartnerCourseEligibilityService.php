<?php

namespace App\Services;

use App\Models\Course;
use App\Models\PartnerCourseMapping;
use App\Models\User;

class PartnerCourseEligibilityService
{
    public function resolveStartocodeMappingForUser(User $user): ?PartnerCourseMapping
    {
        $courseId = $user->registered_course ?: $user->admissions()->latest()->value('course_id');
        $course = $courseId ? Course::find($courseId) : null;
        $courseName = strtolower(trim((string) ($course?->course_name ?? '')));

        $partnerCode = (string) config('services.partner_startocode.code', 'startocode');

        $baseQuery = PartnerCourseMapping::query()
            ->where('partner_code', $partnerCode)
            ->where('is_active', true);

        if ($courseId) {
            $exact = (clone $baseQuery)->where('course_id', $courseId)->first();
            if ($exact) {
                return $exact;
            }
        }

        if ($courseName === '') {
            return null;
        }

        $mappings = (clone $baseQuery)
            ->whereNotNull('course_name_pattern')
            ->get();

        foreach ($mappings as $mapping) {
            $pattern = strtolower(trim((string) $mapping->course_name_pattern));
            if ($pattern !== '' && str_contains($courseName, $pattern)) {
                return $mapping;
            }
        }

        return null;
    }
}
