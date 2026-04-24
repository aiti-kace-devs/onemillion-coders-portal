<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\ProgrammeBatch;
use App\Services\AvailabilityService;
use App\Services\GhanaCardService;
use App\Services\InPersonEnrollmentService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InPersonEnrollmentController extends Controller
{
    /**
     * POST /api/in-person-enrollment
     */
    public function store(
        Request $request,
        InPersonEnrollmentService $enrollmentService,
        AvailabilityService $availabilityService,
        GhanaCardService $ghanaCardService
    ): JsonResponse {
        $validated = $request->validate([
            'programme_batch_id' => 'required|integer|exists:programme_batches,id',
            'course_id' => 'required|integer|exists:courses,id',
            'course_session_id' => 'nullable|integer',
            'session_id' => 'nullable|integer',
            'capacity_pool' => 'nullable|string|in:reserved,standard',
        ]);

        $sessionId = (int) ($validated['course_session_id'] ?? $validated['session_id'] ?? 0);
        if ($sessionId < 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected session id is invalid.',
                'errors' => ['session_id' => ['The selected session id is invalid.']],
            ], 422);
        }

        $batch = ProgrammeBatch::find($validated['programme_batch_id']);
        if (! $batch) {
            return response()->json([
                'status' => 'error',
                'message' => 'Programme batch not found.',
            ], 404);
        }

        if (! $batch->status) {
            return response()->json([
                'status' => 'error',
                'message' => 'Programme batch is not active.',
            ], 422);
        }

        $user = $request->user();
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        if (! $ghanaCardService->isVerified($user)) {
            $verificationStatus = $ghanaCardService->buildStatus($user);

            return response()->json([
                'status' => 'error',
                'message' => 'Please complete Ghana Card verification before enrolling.',
                'error' => ['code' => 'verification_required'],
                'meta' => [
                    'attempts' => $verificationStatus['attempts'],
                    'blocked' => $verificationStatus['blocked'],
                ],
            ], 403);
        }

        $course = Course::with('programme')->find($validated['course_id']);
        $centreSession = CourseSession::find($sessionId);

        if (! $course) {
            return response()->json([
                'status' => 'error',
                'message' => 'Course not found.',
            ], 404);
        }

        if (! $course->isInPersonProgramme()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Course is not an in-person programme.',
            ], 422);
        }

        if (! $centreSession
            || ! $centreSession->status
            || (int) $centreSession->centre_id !== (int) $course->centre_id
            || $centreSession->session_type !== CourseSession::TYPE_CENTRE
            || (int) $centreSession->course_id !== (int) $course->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Course or session not found.',
            ], 404);
        }

        try {
            $enrollmentService->enroll($user, $course, $batch, $centreSession, $validated['capacity_pool'] ?? null);
        } catch (Exception $e) {
            $forProtocol = (bool) ($user->is_protocol ?? false);
            $recommendations = $availabilityService->getAvailableSlots(
                $course->centre_id,
                $course->id,
                Carbon::parse($batch->start_date),
                Carbon::parse($batch->end_date),
                $forProtocol
            );

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'recommendations' => $recommendations['recommendations'] ?? [],
            ], 409);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Enrollment successful.',
            // Browser should follow this (same app URL as API session / APP_URL); avoids relying on NEXT_PUBLIC_PORTAL_URL alone.
            'redirect_url' => url('/student/dashboard'),
        ], 201);
    }
}
