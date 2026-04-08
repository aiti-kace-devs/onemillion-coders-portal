<?php

namespace App\Services;

use App\Models\Course;
use App\Models\PartnerCourseMapping;
use App\Models\PartnerIntegration;
use App\Models\Programme;
use App\Models\User;
use App\Support\PartnerCodeNormalizer;
use Illuminate\Support\Facades\Schema;

class PartnerCourseEligibilityService
{
    public function resolveMappingForUser(User $user, string $partnerCode): ?PartnerCourseMapping
    {
        $partnerCode = PartnerCodeNormalizer::normalize($partnerCode);
        if ($partnerCode === '') {
            return null;
        }

        $courseId = $user->registered_course ?: $user->admissions()->latest()->value('course_id');
        $course = $courseId ? Course::with('programme')->find($courseId) : null;

        if (! $this->courseEligibleForPartnerProgress($course, $partnerCode)) {
            return null;
        }

        $courseName = strtolower(trim((string) ($course?->course_name ?? '')));

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

    /**
     * Resolve the active partner mapping for a user across all partners.
     * The course's programme must declare a provider that matches the mapping's partner_code,
     * and that partner must have an enabled integration with a base URL.
     */
    public function resolveAnyMappingForUser(User $user): ?PartnerCourseMapping
    {
        $courseId = $user->registered_course ?: $user->admissions()->latest()->value('course_id');
        $course = $courseId ? Course::with('programme')->find($courseId) : null;

        $programmePartnerCode = $this->normalizedProgrammeProvider($course?->programme);
        if ($programmePartnerCode === null || ! $this->hasConfiguredIntegration($programmePartnerCode)) {
            return null;
        }

        $courseName = strtolower(trim((string) ($course?->course_name ?? '')));

        $baseQuery = PartnerCourseMapping::query()
            ->where('is_active', true)
            ->where('partner_code', $programmePartnerCode);

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

    /**
     * Programme declares the tuition provider; it must match the requested partner and be configured in Admin.
     */
    private function courseEligibleForPartnerProgress(?Course $course, string $requestedPartnerCode): bool
    {
        $requestedPartnerCode = PartnerCodeNormalizer::normalize($requestedPartnerCode);
        $programmePartnerCode = $this->normalizedProgrammeProvider($course?->programme);

        if ($programmePartnerCode === null || $programmePartnerCode !== $requestedPartnerCode) {
            return false;
        }

        return $this->hasConfiguredIntegration($programmePartnerCode);
    }

    private function normalizedProgrammeProvider(?Programme $programme): ?string
    {
        if ($programme === null) {
            return null;
        }

        $code = PartnerCodeNormalizer::normalize(trim((string) ($programme->provider ?? '')));

        return $code === '' ? null : $code;
    }

    private function hasConfiguredIntegration(string $normalizedPartnerCode): bool
    {
        if (! Schema::hasTable('partner_integrations')) {
            return false;
        }

        return PartnerIntegration::query()
            ->where('partner_code', $normalizedPartnerCode)
            ->where('is_enabled', true)
            ->whereNotNull('base_url')
            ->where('base_url', '!=', '')
            ->exists();
    }
}
