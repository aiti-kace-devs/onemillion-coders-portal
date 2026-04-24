<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InPersonAvailabilityController extends Controller
{
    /**
     * GET /api/availability/in-person/batches?course_id=
     *
     * This endpoint intentionally delegates to the shared batches endpoint so
     * in-person availability follows the same rules everywhere.
     */
    public function batches(Request $request, BookingService $bookingService): JsonResponse
    {
        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        $course = Course::with('programme')->find((int) $request->input('course_id'));

        if (! $course || ! $course->programme || ! $course->isInPersonProgramme()) {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint is only for in-person courses. Use /api/availability/batches for online programmes.',
            ], 422);
        }

        return app(AvailabilityController::class)->batches($request, $bookingService);
    }
}
