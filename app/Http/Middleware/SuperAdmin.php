<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('admin')->user() == null) {
            return redirect(route('admin.login'));
        }
        if (Auth::guard('admin')->user()->hasRole('super-admin', 'admin')) {
            return $next($request);
        }
        return redirect()->back()->with([
            'flash' => 'Unauthorized',
            'key' => 'error'
        ]);
    }
}
