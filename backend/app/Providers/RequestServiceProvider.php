<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class RequestServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Ensure request is bound early
        $this->app->singleton('request', function ($app) {
            if (php_sapi_name() === 'cli') {
                // For CLI requests, create a basic request
                return new Request();
            }
            return Request::capture();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
