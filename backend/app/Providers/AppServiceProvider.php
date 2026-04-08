<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Cache\RateLimiting\Limit;
use App\Models\PartnerIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Statamic\Facades\CP\Nav;
use Illuminate\Support\Facades\Validator;
use App\Rules\Recaptcha;
use App\Services\JwtService;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Bind custom AssetContainerContents to handle GCS dirname issue
        $this->app->bind(
            \Statamic\Assets\AssetContainerContents::class,
            \App\Overrides\AssetContainerContents::class
        );

        // Replace Statamic import-assets command with GCS-compatible version
        $this->app->bind(
            \Statamic\Eloquent\Commands\ImportAssets::class,
            \App\Console\Commands\ImportAssetsCommand::class
        );

        // Replace Statamic export-assets command with writeMeta null fix
        $this->app->bind(
            \Statamic\Eloquent\Commands\ExportAssets::class,
            \App\Console\Commands\ExportAssetsCommand::class
        );

        $this->app->singleton(JwtService::class, fn() => JwtService::fromConfig());

        // Partner progress drivers registry (pluggable partner integrations).
        $this->app->singleton(\App\Services\Partners\Generic\ProgressMappingNormalizer::class);
        $this->app->singleton(\App\Services\Partners\Generic\GenericProgressDriverFactory::class);
        $this->app->singleton(\App\Services\Partners\PartnerProgressPayloadValidator::class);
        $this->app->singleton(\App\Services\Partners\PartnerIntegrityService::class);
        $this->app->singleton(\App\Services\Partners\PartnerRegistry::class, function ($app) {
            return new \App\Services\Partners\PartnerRegistry(
                [
                    $app->make(\App\Services\Partners\Startocode\StartocodeProgressDriver::class),
                ],
                $app->make(\App\Services\Partners\Generic\GenericProgressDriverFactory::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        RateLimiter::for('partner-progress-bulk', function (object $job) {
            $partnerCode = property_exists($job, 'partnerCode') ? (string) ($job->partnerCode ?? 'unknown') : 'unknown';
            $perMinute = 20;
            if ($partnerCode !== '' && $partnerCode !== 'unknown' && Schema::hasTable('partner_integrations')) {
                $configured = PartnerIntegration::query()
                    ->where('partner_code', $partnerCode)
                    ->value('rate_limit_per_minute');
                if ($configured !== null && (int) $configured > 0) {
                    $perMinute = (int) $configured;
                }
            }

            return Limit::perMinute(max(1, $perMinute))->by("bulk:{$partnerCode}");
        });

        RateLimiter::for('partner-progress-refresh', function (object $job) {
            $partnerCode = property_exists($job, 'partnerCode') ? (string) ($job->partnerCode ?? 'unknown') : 'unknown';
            $userId = property_exists($job, 'userId') ? (string) ($job->userId ?? '0') : '0';
            return Limit::perMinute(60)->by("refresh:{$partnerCode}:{$userId}");
        });

        // Windows / corporate PHP often lacks an up-to-date CA bundle; cURL error 60 when Basset or Http fetches HTTPS.
        // Drop https://curl.se/ca/cacert.pem into storage/app/cacert.pem (gitignored) or set BASSET_VERIFY_SSL_CERTIFICATE.
        if ($this->app->environment('local')) {
            $cacert = storage_path('app/cacert.pem');
            if (is_file($cacert)) {
                Http::globalOptions(['verify' => $cacert]);
            }
        }

        // Paginator::useBootstrapFour();

        if ($this->app->isLocal()) {
            // Set CSP nonce for Laravel Debugbar during development
            if (class_exists(\Barryvdh\Debugbar\Facades\Debugbar::class) && app()->bound('debugbar')) {
                app('debugbar')->getJavascriptRenderer()->setCspNonce(csp_nonce());
            }
        }

        if ($this->app->isProduction()) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
            // This fixes the crash during 'php artisan basset:cache'
            $maxTime = ini_get('max_execution_time');

            // If it's a numeric string (common in GCP/Docker), cast it to int
            if (is_numeric($maxTime)) {
                @set_time_limit((int)$maxTime);
            }
            // Horizon::routeSlackNotificationsTo(env('LOG_SLACK_WEBHOOK_URL', ''),  env('SLACK_CHANNEL', '#general'));
        }

        $this->app->singleton('SMSLogger', function ($app) {
            return new \App\Logging\SMSLogger();
        });

        $this->app->bind(
            \Backpack\PermissionManager\app\Http\Controllers\UserCrudController::class, //this is package controller
            \App\Http\Controllers\Admin\UserCrudController::class //this should be your own controller
        );

        // View composer for $mailable in Backpack modals
        View::composer([
            'admin.send-bulk-email',
            'vendor.backpack.crud.modals.bulk_email',
        ], function ($view) {
            // Replace this with your actual mailables logic
            $view->with('mailable', [
                'WelcomeMail',
                'ReminderMail',
                'NewsletterMail',
            ]);
        });

        View::composer('vendor.backpack.crud.modals.bulk_email', function ($view) {
            $view->with('mailable', \App\Helpers\MailerHelper::getMailableClasses());
        });

        View::composer('vendor.backpack.crud.modals.admit', function ($view) {
            $view->with('courses', \App\Models\Course::pluck('course_name', 'id')->toArray());
            $view->with('sessions', \App\Models\CourseSession::courseType()->get());
        });

        // Add Backpack Dashboard link to Statamic navigation
        Nav::extend(function ($nav) {
            // Remove Users/Roles sections from Statamic navigation to avoid conflicts with Backpack
            $nav->remove('Users');

            // Add Backpack dashboard link
            $nav->create('Backpack')
                ->icon('terminal')
                ->section('Tools')
                ->url(route('backpack.dashboard'));
        });

        Validator::extend('recaptcha', function ($attribute, $value, $parameters, $validator) {
            $rule = new Recaptcha;
            $passed = true;

            // Call the rule manually
            $rule->validate($attribute, $value, function ($message) use (&$passed) {
                $passed = false;
            });

            return $passed;
        });
    }
}
