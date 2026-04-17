<?php

namespace App\Http\Middleware;

use App\Services\GhanaCardService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentVerificationFlow
{
    public function __construct(private readonly GhanaCardService $ghanaCardService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if (! $this->isRestrictedRoute($request)) {
            return $next($request);
        }

        if ($this->ghanaCardService->isVerified($user)) {
            return $next($request);
        }

        $status = $this->ghanaCardService->buildStatus($user);
        $message = $status['blocked']
            ? (string) data_get($status, 'block.message', 'Your verification is currently blocked. Please contact support or an administrator.')
            : 'Please complete Ghana Card verification before proceeding to this step.';

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'verification_required',
                    'message' => $message,
                ],
                'meta' => [
                    'attempts' => $status['attempts'],
                    'blocked' => $status['blocked'],
                    'block' => $status['block'] ?? null,
                ],
            ], 403);
        }

        return redirect()
            ->route('student.verification.index')
            ->with([
                'flash' => $message,
                'key' => 'warning',
            ]);
    }

    private function isRestrictedRoute(Request $request): bool
    {
        if ($request->is('api/student/session-options') || $request->is('api/student/session-confirm')) {
            return true;
        }

        $routeName = (string) $request->route()?->getName();
        if ($routeName === '') {
            return false;
        }

        $restrictedPatterns = [
            'student.session.*',
            'student.course.*',
            'student.change-course',
            'student.update-course',
            'student.select-session',
            'student.delete-student-admission',
            'api.bookings.*',
        ];

        foreach ($restrictedPatterns as $pattern) {
            if ($request->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }
}
