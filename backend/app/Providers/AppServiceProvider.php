<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
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
            $view->with('sessions', \App\Models\CourseSession::all());
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
