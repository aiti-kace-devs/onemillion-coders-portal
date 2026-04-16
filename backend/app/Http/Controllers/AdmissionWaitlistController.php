<?php

namespace App\Http\Controllers;

use App\Models\AdmissionWaitlist;
use App\Models\Course;
use App\Services\AdmissionWaitlistService;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdmissionWaitlistController extends Controller
{
    public function __construct(
        protected AdmissionWaitlistService $waitlistService,
        protected BookingService $bookingService
    ) {}

    /**
     * Add user to waitlist for a course.
     * POST /api/waitlist/add
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'userId' => 'required|string|exists:users,userId',
            'programme_batch_id' => 'nullable|integer|exists:programme_batches,id',
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $course = Course::with('programme')->find($validated['course_id']);
        if (! $course) {
            return response()->json(['status' => 'error', 'message' => 'Course not found.'], 404);
        }

        try {
            $waitlist = $this->waitlistService->addToWaitlist(
                $user,
                $course,
                $validated['programme_batch_id'] ?? null
            );

            return response()->json([
                'status' => 'success',
                'message' => 'You have been added to the waitlist.',
                // 'data' => $waitlist->load(['course.programme', 'course.centre']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove user from waitlist.
     * DELETE /api/waitlist/{courseId}
     */
    public function destroy(Request $request, int $courseId): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $removed = $this->waitlistService->removeFromWaitlist($user, $courseId);

        if (! $removed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Waitlist entry not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'You have been removed from the waitlist.',
        ]);
    }

    /**
     * Check if user is on waitlist for a course.
     * GET /api/waitlist/check/{courseId}
     */
    public function check(Request $request, int $courseId): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $isOnWaitlist = $this->waitlistService->isOnWaitlist($user, $courseId);

        return response()->json([
            'status' => 'success',
            'on_waitlist' => $isOnWaitlist,
        ]);
    }

    /**
     * Get all waitlist entries for the authenticated user.
     * GET /api/waitlist/mine
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $waitlist = $this->waitlistService->getUserWaitlist($user);

        return response()->json([
            'status' => 'success',
            'data' => $waitlist,
        ]);
    }

    /**
     * Convert a waitlist entry to a booking.
     * POST /api/waitlist/convert/{waitlistId}
     */
    public function convert(Request $request, int $waitlistId): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $waitlist = AdmissionWaitlist::where('id', $waitlistId)
            ->where('user_id', $user->userId)
            ->first();

        if (! $waitlist) {
            return response()->json([
                'status' => 'error',
                'message' => 'Waitlist entry not found.',
            ], 404);
        }

        try {
            $booking = $this->waitlistService->convertWaitlistEntry($waitlist, $this->bookingService);

            return response()->json([
                'status' => 'success',
                'message' => 'Booking created successfully.',
                'data' => $booking->load(['programmeBatch', 'courseSession', 'course']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get waitlist count for a course.
     * GET /api/waitlist/count/{courseId}
     */
    public function count(int $courseId): JsonResponse
    {
        $count = $this->waitlistService->getWaitlistCount($courseId);

        return response()->json([
            'status' => 'success',
            'course_id' => $courseId,
            'waitlist_count' => $count,
        ]);
    }
}
