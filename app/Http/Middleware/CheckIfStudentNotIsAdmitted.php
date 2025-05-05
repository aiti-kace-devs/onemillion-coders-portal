<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckIfStudentNotIsAdmitted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();
        $user = Auth::user();

        $admitted = $user->isAdmitted();

        if (!$admin && $admitted) {
            return redirect('/student/id-qrcode')->with([
                'flash' => 'You are already admitted.',
                'key' => 'success'
            ]);
        }

        return $next($request);
    }
}
