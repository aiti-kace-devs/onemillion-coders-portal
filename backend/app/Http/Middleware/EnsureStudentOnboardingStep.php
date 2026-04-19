<?php

namespace App\Http\Middleware;

use App\Services\StudentOnboardingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentOnboardingStep
{
    public function __construct(private readonly StudentOnboardingService $onboarding) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $blockingStep = $this->onboarding->getBlockingStep($user);

        if ($this->onboarding->isRequestAllowedForBlockingStep($request, $blockingStep)) {
            return $next($request);
        }

        $targetRoute = $this->onboarding->routeNameForStep((string) $blockingStep);
        $message = match ($blockingStep) {
            StudentOnboardingService::STEP_APPLICATION_REVIEW => 'Please review the application information before continuing.',
            StudentOnboardingService::STEP_ASSESSMENT => 'Please complete the level assessment before continuing.',
            StudentOnboardingService::STEP_IDENTITY_VERIFICATION => 'Please complete identity verification before continuing.',
            StudentOnboardingService::STEP_COURSE_SELECTION => 'Please select a course before continuing.',
            default => 'Please complete the required enrollment step before continuing.',
        };

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'onboarding_step_required',
                    'message' => $message,
                ],
                'meta' => [
                    'blocking_step' => $blockingStep,
                    'redirect_route' => $targetRoute,
                ],
            ], 403);
        }

        // UX: keep students on the dashboard and guide them with banners/CTAs,
        // instead of forcefully bouncing them into a different page.
        return redirect()
            ->route('student.dashboard')
            ->with([
                'flash' => $message,
                'key' => 'warning',
                'onboarding' => [
                    'blocking_step' => $blockingStep,
                    'next_route' => $targetRoute,
                ],
            ]);
    }
}
