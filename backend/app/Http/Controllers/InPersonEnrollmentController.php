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
            'course_session_id' => 'required|integer|exists:course_sessions,id',
        ]);

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
        $centreSession = CourseSession::find($validated['course_session_id']);

        if (! $course || ! $centreSession) {
            return response()->json([
                'status' => 'error',
                'message' => 'Course or session not found.',
            ], 404);
        }

        if (! $course->isInPersonProgramme()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Course is not an in-person programme.',
            ], 422);
        }

        try {
            $enrollmentService->enroll($user, $course, $batch, $centreSession);
        } catch (Exception $e) {
            $recommendations = $availabilityService->getAvailableSlots(
                $course->centre_id,
                $course->id,
                Carbon::parse($batch->start_date),
                Carbon::parse($batch->end_date)
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
