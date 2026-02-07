<?php

namespace App\Http\Middleware;

use App\Models\AppConfig;
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
        $configs = AppConfig::pluck('value', 'key')->all();

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
            'config' => $configs,
            'flash' => [
                'message' => fn() => $request->session()->get('flash'),
                'key' => fn() => $request->session()->get('key'),
            ],
        ];
    }
}
