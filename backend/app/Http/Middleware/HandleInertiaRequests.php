<?php

namespace App\Http\Middleware;

use App\Models\AppConfig;
use App\Services\JwtService;
use App\Services\PartnerCourseEligibilityService;
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
    public function version(Request $request): string|null
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
                    'user' => $request->user()
                ]
            ];
        }

        $user = Auth::guard('web')->user();

        $configKeys = [
            SHOW_RESULTS_TO_STUDENTS,
            SHOW_STUDENT_LEVEL,
            SHOW_COURSE_ASSESSMENT_TO_STUDENTS,
            ALLOW_COURSE_CHANGE,
            ALLOW_SESSION_CHANGE,
            EXAM_DEADLINE_AFTER_REGISTRATION
        ];

        $configs = [];
        foreach ($configKeys as $key) {
            $configs[$key] = config($key);
        }

        $quizJwtToken = null;
        $hasPartnerProgressMenu = false;
        if ($user) {
            $quizJwtToken = app(JwtService::class)->generate($user->id);
            $hasPartnerProgressMenu = (bool) config('services.partner_startocode.enable_student_progress_menu', true)
                && app(PartnerCourseEligibilityService::class)->resolveStartocodeMappingForUser($user) !== null;
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user
                    ? array_merge(
                        $user->only('id', 'name', 'email', 'created_at', 'userId', 'registered_course', 'shortlist'),
                        [
                            'isAdmitted' => $user?->isAdmitted(),
                            'hasAdmission' => $user?->hasAdmission(),
                            'hasAttendance' => $user?->hasAttendance(),
                            'assessment_completed' => $user?->userAssessment?->completed ?? false,
                            'hasPartnerProgressMenu' => $hasPartnerProgressMenu,
                        ]
                    )
                    : null,
                'unreadNotifications' => $user ? $user->notifications()->unread()->count() : 0,
            ],
            'config' => $configs,
            'quiz_frontend_url' => config('app.quiz_frontend_url'),
            'quiz_jwt_token' => $quizJwtToken,
            'flash' => [
                'message' => fn() => $request->session()->get('flash'),
                'key' => fn() => $request->session()->get('key'),
            ],
            'recaptcha_site_key' => config('services.recaptcha.site_key'),
        ];
    }
}
