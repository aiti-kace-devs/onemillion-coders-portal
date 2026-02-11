<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckIfStudentIsAdmitted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, bool $withSession = false): Response
    {
        $student = Auth::guard('web')->user();

        $admitted = $student->hasAdmission();

        if ($withSession) {
            $admitted = $student->admission?->confirmed ?? false;
        }

        if (!$admitted) {
            return redirect(route('student.dashboard'))->with([
                'flash' => 'You are not admitted.',
                'key' => 'error'
            ]);
        }

        return $next($request);
    }
}
