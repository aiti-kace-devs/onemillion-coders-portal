<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->remove('X-Powered-By');
        if (app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        $cspHeader = "default-src 'self'; " .
                    "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://fonts.googleapis.com https://fonts.gstatic.com https://cdn.datatables.net https://cdn.rawgit.com https://maxcdn.bootstrapcdn.com https://code.jquery.com; " .
                    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.gstatic.com https://cdn.datatables.net https://cdn.rawgit.com https://maxcdn.bootstrapcdn.com; " .
                    "font-src 'self' data: https://fonts.googleapis.com https://fonts.gstatic.com; " .
                    "img-src 'self' data:; " .
                    "connect-src 'self';";

        $response->headers->set('Content-Security-Policy', $cspHeader);

        
        // $response->headers->set('X-Content-Type-Options', 'nosniff');
        // $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Permissions-Policy', 'camera=(self), fullscreen=(self)');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('X-Powered-By', 'A Dedicated Development Team :)');

        return $response;
    }
}
