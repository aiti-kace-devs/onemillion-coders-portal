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
        if (! $request->expectsJson()) {
            // Check if the current route is within the admin group
            if ($request->is('admin*')) {
                return backpack_url('/admin/login');
            }

            // Default for everyone else (students)
            return route('login');
        }

        return null;
    }
}
