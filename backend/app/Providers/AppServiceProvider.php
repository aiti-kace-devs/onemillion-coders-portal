<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrapFour();

        if ($this->app->isLocal()) {
            // Set CSP nonce for Laravel Debugbar during development
            if (class_exists(\Barryvdh\Debugbar\Facades\Debugbar::class) && app()->bound('debugbar')) {
                app('debugbar')->getJavascriptRenderer()->setCspNonce(csp_nonce());
            }
        }

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
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
        \View::composer([
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
    }
}
