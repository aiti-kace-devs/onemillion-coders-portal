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
    public const HOME = 'student/dashboard';
    public const ADMIN_HOME = '/admin/dashboard';

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
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
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
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        RateLimiter::for('otp-check-email', function (Request $request) {
            $email = strtolower(trim((string) $request->query('email', '')));
            $emailKey = $email !== '' ? sha1($email) : 'missing-email';

            return [
                // Protect origin from broad bursts (shared/NAT IPs still get generous room).
                Limit::perMinute(3000)->by('otp-check-email:ip:'.$request->ip()),
                // Prevent hammering a single email lookup while allowing normal typing/debounce.
                Limit::perMinute(120)->by('otp-check-email:email:'.$request->ip().':'.$emailKey),
            ];
        });

        RateLimiter::for('otp-send', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email', '')));
            $emailKey = $email !== '' ? sha1($email) : 'missing-email';

            return [
                // High ceiling for shared/NAT IP traffic; business rules still gate OTP issuance.
                Limit::perMinute(1200)->by('otp-send:ip:'.$request->ip()),
                // Additional abuse guard per email+IP pair.
                Limit::perMinute(30)->by('otp-send:email:'.$request->ip().':'.$emailKey),
            ];
        });

        RateLimiter::for('availability-per-centre', function (Request $request) {
            $userId = optional($request->user())->id;
            $actorKey = $userId ? 'user:'.$userId : 'ip:'.$request->ip();

            // Higher throughput for this read-heavy endpoint while retaining abuse protection.
            return Limit::perMinute(1200)->by('availability-per-centre:'.$actorKey);
        });
    }
}
