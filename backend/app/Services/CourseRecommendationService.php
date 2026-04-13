<?php

namespace App\Services;

use App\Models\AdmissionWaitlist;
use App\Models\Centre;
use App\Models\Course;
use App\Models\CourseBatch;
use App\Models\CourseSession;

class CourseRecommendationService
{
    /**
     * Priority constants for recommendation types.
     */
    public const PRIORITY_ALT_CENTRE   = 1; // Same course, different centre (same branch)
    public const PRIORITY_ALT_COURSE   = 2; // Different course, same centre
    public const PRIORITY_NO_SUPPORT   = 3; // No centre support (online / self-paced)
    public const PRIORITY_WAITLIST     = 4; // Join the waitlist

    public function __construct(private QuotaService $quotaService) {}

    /**
     * Find the best available alternative when a course/session is full.
     *
     * Returns an array with shape:
     *   ['priority' => int, 'type' => string, 'data' => array]
     */
    public function findAlternative(Course $course, string $userId): array
    {
        // Priority 1: Same programme, different centre in the same branch
        $altCentre = $this->findSameProgrammeSameBranch($course, $userId);
        if ($altCentre) {
            return [
                'priority' => self::PRIORITY_ALT_CENTRE,
                'type'     => 'alt_centre',
                'data'     => $altCentre,
            ];
        }

        // Priority 2: Different programme/course at the same centre
        $altCourse = $this->findAltProgrammeSameCentre($course, $userId);
        if ($altCourse) {
            return [
                'priority' => self::PRIORITY_ALT_COURSE,
                'type'     => 'alt_course',
                'data'     => $altCourse,
            ];
        }

        // Priority 3: No centre support option (online delivery)
        if ($course->programme?->isOnline() || $this->hasOnlineAlternative($course)) {
            return [
                'priority' => self::PRIORITY_NO_SUPPORT,
                'type'     => 'no_centre_support',
                'data'     => [
                    'message' => 'You can complete this programme without attending a physical centre.',
                ],
            ];
        }

        // Priority 4: Waitlist
        return [
            'priority' => self::PRIORITY_WAITLIST,
            'type'     => 'waitlist',
            'data'     => [
                'message' => 'No slots are currently available. You can join the waitlist and will be notified when a slot opens.',
                'course_id' => $course->id,
            ],
        ];
    }

    /**
     * Add a user to the waitlist for a course.
     */
    public function addToWaitlist(string $userId, int $courseId): bool
    {
        $entry = AdmissionWaitlist::firstOrCreate(
            ['user_id' => $userId, 'course_id' => $courseId]
        );

        return $entry->wasRecentlyCreated;
    }

    /**
     * Remove a user from the waitlist for a course.
     */
    public function removeFromWaitlist(string $userId, int $courseId): void
    {
        AdmissionWaitlist::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->delete();
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * Find a course with the same programme at another centre in the same branch, with available slots.
     */
    private function findSameProgrammeSameBranch(Course $course, string $userId): ?array
    {
        if (!$course->programme_id || !$course->centre?->branch_id) {
            return null;
        }

        $today = now()->toDateString();

        $altCourse = Course::query()
            ->where('programme_id', $course->programme_id)
            ->where('id', '!=', $course->id)
            ->whereHas('centre', function ($q) use ($course) {
                $q->where('branch_id', $course->centre->branch_id)
                    ->where('id', '!=', $course->centre_id);
            })
            ->whereHas('programmeBatches', function ($q) use ($today) {
                $q->where('end_date', '>=', $today)
                    ->where('available_slots', '>', 0);
            })
            ->with(['centre:id,title', 'programme:id,title'])
            ->first();

        if (!$altCourse) {
            return null;
        }

        return [
            'course_id'    => $altCourse->id,
            'course_name'  => $altCourse->course_name,
            'centre_id'    => $altCourse->centre_id,
            'centre_name'  => $altCourse->centre?->title,
            'programme_id' => $altCourse->programme_id,
        ];
    }

    /**
     * Find a different course at the same centre with available slots.
     */
    private function findAltProgrammeSameCentre(Course $course, string $userId): ?array
    {
        if (!$course->centre_id) {
            return null;
        }

        $today = now()->toDateString();

        $altCourse = Course::query()
            ->where('centre_id', $course->centre_id)
            ->where('id', '!=', $course->id)
            ->whereHas('programmeBatches', function ($q) use ($today) {
                $q->where('end_date', '>=', $today)
                    ->where('available_slots', '>', 0);
            })
            ->with(['programme:id,title'])
            ->first();

        if (!$altCourse) {
            return null;
        }

        return [
            'course_id'       => $altCourse->id,
            'course_name'     => $altCourse->course_name,
            'programme_id'    => $altCourse->programme_id,
            'programme_title' => $altCourse->programme?->title,
        ];
    }

    private function hasOnlineAlternative(Course $course): bool
    {
        if (!$course->programme_id) {
            return false;
        }

        return Course::query()
            ->where('programme_id', $course->programme_id)
            ->whereHas('programme', fn($q) => $q->where('mode_of_delivery', 'online'))
            ->exists();
    }
}
