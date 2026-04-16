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
        // $response->headers->set('X-Content-Type-Options', 'nosniff');
        // $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $trustedCameraOrigins = ['self'];

        $configOrigin = $this->extractOrigin((string) config('app.quiz_frontend_url', ''));
        if ($configOrigin) {
            $trustedCameraOrigins[] = '"' . $configOrigin . '"';
        }

        $requestOrigin = $this->extractOrigin((string) $request->headers->get('Origin', ''));
        if ($requestOrigin) {
            $trustedCameraOrigins[] = '"' . $requestOrigin . '"';
        }

        $refererOrigin = $this->extractOrigin((string) $request->headers->get('Referer', ''));
        if ($refererOrigin) {
            $trustedCameraOrigins[] = '"' . $refererOrigin . '"';
        }

        $cameraDirective = 'camera=(' . implode(' ', array_unique($trustedCameraOrigins)) . ')';
        $response->headers->set('Permissions-Policy', $cameraDirective . ', fullscreen=(self)');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('X-Powered-By', 'A Dedicated Development Team :)');

        return $response;
    }

    private function extractOrigin(string $url): ?string
    {
        if ($url === '') {
            return null;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);

        if (!is_string($scheme) || !in_array($scheme, ['http', 'https'], true) || !is_string($host) || $host === '') {
            return null;
        }

        return rtrim($scheme . '://' . $host . ($port ? ':' . $port : ''), '/');
    }
}
