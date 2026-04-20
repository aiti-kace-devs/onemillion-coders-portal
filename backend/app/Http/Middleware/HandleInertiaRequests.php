<?php

namespace App\Http\Middleware;

use App\Models\AdmissionWaitlist;
use App\Services\JwtService;
use App\Services\StudentOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {

        $route = Route::current();

        if ($request->is('admin*')) {
            return [
                ...parent::share($request),
                'auth' => [
                    'user' => $request->user(),
                ],
            ];
        }

        $user = Auth::guard('web')->user();

        $configKeys = [
            SHOW_STUDENT_LEVEL,
            SHOW_COURSE_ASSESSMENT_TO_STUDENTS,
            ALLOW_COURSE_CHANGE,
            ALLOW_SESSION_CHANGE,
        ];

        $configs = [];
        foreach ($configKeys as $key) {
            $configs[$key] = config($key);
        }

        $quizJwtToken = null;
        if ($user && in_array(Route::currentRouteName(), ['student.change-course', 'student.level-assessment', 'student.application-review.index'])) {
            $quizJwtToken = app(JwtService::class)->generate($user->id);
        }

        $onboardingStep = $user ? app(StudentOnboardingService::class)->getBlockingStep($user) : null;

        $isOnWaitlist = $user && ! $user->registered_course
            ? AdmissionWaitlist::where('user_id', $user->userId)
                ->whereIn('status', ['pending', 'notified'])
                ->exists()
            : false;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user
                    ? array_merge(
                        $user->only('id', 'name', 'userId', 'registered_course', 'shortlist'),
                        Route::currentRouteName() === 'student.profile.edit'
                        ? $user->only('email', 'created_at')
                        : [],
                        [
                            'isAdmitted' => $user?->isAdmitted(),
                            'hasAdmission' => $user?->hasAdmission(),
                            'hasAttendance' => $user?->hasAttendance(),
                            'assessment_completed' => $user?->userAssessment?->completed ?? false,
                            'on_waitlist' => $isOnWaitlist,
                            'verification_completed' => $user?->isVerifiedByGhanaCard() ?? false,
                            'verification_blocked' => (bool) ($user?->is_verification_blocked ?? false),
                            'student_level' => config(SHOW_STUDENT_LEVEL, false) ? $user?->student_level : null,
                            'application_review_completed' => $user?->application_review_completed_at !== null,
                            'isOnlineCourse' => $user?->course?->isOnlineProgramme() ?? false,
                            'current_onboarding_step' => $onboardingStep,
                        ]
                    )
                    : null,
                'unreadNotifications' => $user ? $user->notifications()->unread()->count() : 0,
            ],
            'config' => $configs,
            'quiz_frontend_url' => config('app.quiz_frontend_url'),
            'quiz_jwt_token' => $quizJwtToken,
            'flash' => [
                'message' => fn () => $request->session()->get('flash'),
                'key' => fn () => $request->session()->get('key'),
            ],
            'recaptcha_site_key' => $user ? null : config('services.recaptcha.site_key'),
        ];
    }
}
