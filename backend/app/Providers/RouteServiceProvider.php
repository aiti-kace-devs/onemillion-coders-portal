<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public static $HOME = '/student/dashboard';
    public static $ADMIN_HOME = '/admin/dashboard';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $prefix = rtrim(config('app.app_route_prefix', ''), '/');
        self::$HOME = $prefix . '/student/dashboard';
        self::$ADMIN_HOME = $prefix . '/admin/dashboard';

        $this->configureRateLimiting();

        $this->routes(function () {
            $prefix = config('app.app_route_prefix');

            if (!empty($prefix) && $prefix !== '/') {
                if (request()->is($prefix) && !request()->expectsJson()) {
                    Route::redirect($prefix, $prefix . '/login');
                }
            }

            Route::prefix($prefix . '/api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::prefix($prefix)
                ->middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));

            require base_path('routes/backpack/custom.php');
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();

            // 1. Authenticated Users: High limit by User ID
            if ($user) {
                return Limit::perMinute(200)->by($user->id);
            }

            return Limit::perMinute(100)->by($request->ip() . $request->header('User-Agent'));
        });
    }
}
