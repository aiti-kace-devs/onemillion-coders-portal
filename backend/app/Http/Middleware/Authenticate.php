<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // If the request expects JSON (API calls, Statamic API, Axios), 
        // do NOT return a string URL. Returning null tells Laravel to 
        // throw a 401 Unauthorized JSON response instead of a 302 redirect.
        if ($request->expectsJson() || $request->is('api/*') || $request->is('*/api/*')) {
            return null;
        }

        // Handle Backpack Admin redirects
        // config('backpack.base.route_prefix') will now be 'admin' or 'portal/admin'
        // depending on which fix you kept. route() handles it safely either way.
        if ($request->is(config('backpack.base.route_prefix') . '/*')) {
            return route('backpack.auth.login');
        }

        // Default Web Login
        return route('login');
    }
}
