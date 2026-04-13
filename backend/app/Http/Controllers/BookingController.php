<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\ProgrammeBatch;
use App\Models\UserAdmission;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function store(Request $request, BookingService $bookingService, AvailabilityService $availabilityService): JsonResponse
    {
        $validated = $request->validate([
            'programme_batch_id' => 'required|integer|exists:programme_batches,id',
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $batch = ProgrammeBatch::find($validated['programme_batch_id']);
        $course = Course::find($validated['course_id']);

        if ($course->programme_id !== $batch->programme_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Course does not belong to this programme batch.',
            ], 422);
        }

        if ($course->centre_id && $course->centre_id !== $batch->centre_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Course is not offered at the batch centre.',
            ], 422);
        }

        if (!$batch->status) {
            return response()->json([
                'status' => 'error',
                'message' => 'Programme batch is not active.',
            ], 422);
        }

        try {
            $admission = $bookingService->book($user, $course, $batch);
        } catch (Exception $e) {
            $recommendations = $availabilityService->getAvailableSlots(
                $batch->centre_id,
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
            'data' => $admission->load('programmeBatch', 'course'),
        ], 201);
    }

    public function destroy(Request $request, UserAdmission $userAdmission, \App\Services\AdmissionRevocationService $revocationService): JsonResponse
    {
        $user = $request->user();
        if (!$user || $userAdmission->user_id !== $user->userId) {
            return response()->json(['status' => 'error', 'message' => 'Forbidden.'], 403);
        }

        $result = $revocationService->revoke($userAdmission);

        return response()->json([
            'status' => 'success',
            'slot_restored' => $result['slot_restored'],
        ]);
    }

    public function mine(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $bookings = UserAdmission::where('user_id', $user->userId)
            ->whereNotNull('programme_batch_id')
            ->with('programmeBatch.centre', 'programmeBatch.programme', 'course')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $bookings,
        ]);
    }
}
