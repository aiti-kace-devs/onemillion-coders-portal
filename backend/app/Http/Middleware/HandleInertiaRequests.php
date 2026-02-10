<?php

namespace App\Http\Middleware;

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

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user
                    ? array_merge(
                        $user->only('id', 'name', 'email', 'created_at'),
                        [
                            'isAdmitted' => $user?->isAdmitted(),
                            'hasAdmission' => $user?->hasAdmission(),
                            'hasAttendance' => $user?->hasAttendance(),
                        ]
                    )
                    : null,
            ],
            'config' => [
                SHOW_RESULTS_TO_STUDENTS => config(SHOW_RESULTS_TO_STUDENTS),
                ALLOW_COURSE_CHANGE => config(ALLOW_COURSE_CHANGE),
                ALLOW_SESSION_CHANGE => config(ALLOW_SESSION_CHANGE),
                EXAM_DEADLINE_AFTER_REGISTRATION => config(EXAM_DEADLINE_AFTER_REGISTRATION),
            ],
            'flash' => [
                'message' => fn() => $request->session()->get('flash'),
                'key' => fn() => $request->session()->get('key'),
            ],
            'recaptcha_site_key' => config('services.recaptcha.site_key'),
        ];
    }
}
