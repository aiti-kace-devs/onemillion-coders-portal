<?php

namespace App\Services;

use App\Models\AdmissionWaitlist;
use App\Models\User;
use Illuminate\Http\Request;

class StudentOnboardingService
{
    public const STEP_APPLICATION_REVIEW = 'application_review';

    public const STEP_ASSESSMENT = 'assessment';

    public const STEP_IDENTITY_VERIFICATION = 'identity_verification';

    public const STEP_COURSE_SELECTION = 'course_selection';

    public function __construct(private readonly GhanaCardService $ghanaCardService) {}

    /**
     * First incomplete onboarding gate, or null if the student may access the full portal.
     */
    public function getBlockingStep(User $user): ?string
    {
        if ($this->shouldBypassOnboarding($user)) {
            return null;
        }

        if (! $this->hasCompletedApplicationReview($user)) {
            return self::STEP_APPLICATION_REVIEW;
        }

        if (! $this->hasCompletedAssessment($user)) {
            return self::STEP_ASSESSMENT;
        }

        if (! $this->ghanaCardService->isVerified($user)) {
            return self::STEP_IDENTITY_VERIFICATION;
        }

        if (! $this->hasCompletedCourseSelection($user)) {
            return self::STEP_COURSE_SELECTION;
        }

        return null;
    }

    public function shouldBypassOnboarding(User $user): bool
    {
        if ($user->isAdmitted()) {
            return true;
        }

        if ($user->hasAttendance()) {
            return true;
        }

        return false;
    }

    public function hasCompletedApplicationReview(User $user): bool
    {
        return $user->application_review_completed_at !== null;
    }

    public function hasCompletedAssessment(User $user): bool
    {
        return (bool) $user->userAssessment?->completed;
    }

    public function hasCompletedCourseSelection(User $user): bool
    {
        if (! empty($user->registered_course)) {
            return true;
        }

        return AdmissionWaitlist::query()
            ->where('user_id', $user->userId)
            ->whereIn('status', ['pending', 'notified'])
            ->exists();
    }

    public function routeNameForStep(string $step): string
    {
        return match ($step) {
            self::STEP_APPLICATION_REVIEW => 'student.application-review.index',
            self::STEP_ASSESSMENT => 'student.level-assessment',
            self::STEP_IDENTITY_VERIFICATION => 'student.verification.index',
            self::STEP_COURSE_SELECTION => 'student.change-course',
            default => 'student.application-status',
        };
    }

    public function isRequestAllowedForBlockingStep(Request $request, ?string $blockingStep): bool
    {
        if ($blockingStep === null) {
            return true;
        }

        if ($request->routeIs('student.dashboard', 'student.application-status')) {
            return true;
        }

        if ($request->routeIs('student.profile.*')) {
            return true;
        }

        if ($request->routeIs('student.notifications.*')) {
            return true;
        }

        if ($request->routeIs(
            'student.exam.index',
            'student.join-exam',
            'student.start-exam',
            'student.submit-exam',
        )) {
            return true;
        }

        if ($request->routeIs('student.assessment.*')) {
            return true;
        }

        $user = $request->user();
        if (
            $user
            && $request->routeIs('student.verification.*')
            && $this->ghanaCardService->isVerified($user)
        ) {
            return true;
        }

        return match ($blockingStep) {
            self::STEP_APPLICATION_REVIEW => $request->routeIs(
                'student.application-review.index',
                'student.application-review.complete',
                'student.application-status',
            ),
            self::STEP_ASSESSMENT => $request->routeIs(
                'student.level-assessment',
                'student.application-status',
            ) || $request->routeIs('api.tiered-assessment.*'),
            self::STEP_IDENTITY_VERIFICATION => $request->routeIs(
                'student.verification.index',
                'student.verification.status',
                'student.application-status',
            ) || $request->is('api/ghana-card/*'),
            self::STEP_COURSE_SELECTION => $request->routeIs(
                'student.application-status',
                'student.change-course',
                'student.course.index',
                'student.course.select-center',
                'student.course.select-course',
                'student.update-course',
                'student.session.*',
                'student.select-session',
                'student.delete-student-admission',
                'api.bookings.*',
                'api.waitlist.*',
                'api.in-person-enrollment.*',
                'api.availability.*',
            ) || $request->is('api/student/session-options') || $request->is('api/student/session-confirm'),
            default => false,
        };
    }
}
