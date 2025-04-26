<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        DB::connection()
            ->getDoctrineSchemaManager()
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('enum', 'string');

        if ($this->app->isLocal()) {
            // Set CSP nonce for Laravel Debugbar during development
            if (class_exists(\Barryvdh\Debugbar\Facades\Debugbar::class) && app()->bound('debugbar')) {
                app('debugbar')->getJavascriptRenderer()->setCspNonce(csp_nonce());
            }
        }

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        $this->app->singleton('SMSLogger', function ($app) {
            return new \App\Logging\SMSLogger();
        });
    }
}
