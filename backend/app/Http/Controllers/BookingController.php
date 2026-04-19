<?php

namespace App\Http\Controllers;

use App\Models\AdmissionWaitlist;
use App\Models\Booking;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\MasterSession;
use App\Models\ProgrammeBatch;
use App\Models\UserAdmission;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\GhanaCardService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Log;

class BookingController extends Controller
{
    public function store(
        Request $request,
        BookingService $bookingService,
        AvailabilityService $availabilityService,
        GhanaCardService $ghanaCardService
    ): JsonResponse
    {
        // Check if this is a self-paced enrollment (nullable query parameter)
        $isSelfPaced = $request->query('self-paced') === 'true';

        // Adjust validation based on self-paced flag
        $validationRules = [
            'programme_batch_id' => 'required|integer|exists:programme_batches,id',
            'course_id' => 'required|integer|exists:courses,id',
            'session_id' => $isSelfPaced ? 'nullable|integer' : 'required|integer',
        ];

        $validated = $request->validate($validationRules);

        $batch = ProgrammeBatch::find($validated['programme_batch_id']);
        if (!$batch->status) {
            return response()->json([
                'status' => 'error',
                'message' => 'Programme batch is not active.',
            ], 422);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        if (! $ghanaCardService->isVerified($user)) {
            $verificationStatus = $ghanaCardService->buildStatus($user);
            return response()->json([
                'status' => 'error',
                'message' => 'Please complete Ghana Card verification before booking a session.',
                'error' => ['code' => 'verification_required'],
                'meta' => [
                    'attempts' => $verificationStatus['attempts'],
                    'blocked' => $verificationStatus['blocked'],
                ],
            ], 403);
        }

        $course = Course::find($validated['course_id']);
        $programme = $course->programme;
        $isInPerson = strtolower(trim((string) $programme->mode_of_delivery)) === 'in person';

        if ($course->programme_id !== $batch->programme_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Course does not belong to this programme batch.',
            ], 422);
        }

        // Handle self-paced enrollment (no session required)
        if ($isSelfPaced) {
            $user->registered_course = $course->id;
            $user->shortlist = true;
            $user->save();

            UserAdmission::updateOrCreate(
                ['user_id' => $user->userId],
                [
                    'programme_batch_id' => $batch->id,
                    'email_sent' => now(),
                    'confirmed' => now(),
                    'course_id' => $course->id,
                ]
            );

            // Remove from waitlist if exists
            AdmissionWaitlist::where('user_id', $user->userId)->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Self-paced enrollment successful.',
            ], 201);
        }

        // Fetch session for non-self-paced enrollments
        if ($isInPerson) {
            $session = CourseSession::find($validated['session_id']);
            if (!$session || $session->course_id !== $course->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The selected session id is invalid.',
                    'errors' => ['session_id' => ['The selected session id is invalid.']],
                ], 422);
            }
            else {
                        $user->registered_course = $course->id;
                        $user->shortlist = true;
                        $user->save();
                        UserAdmission::updateOrCreate(
                            ['user_id' => $user->userId],
                            [
                                'course_id' => $course->id,
                                'programme_batch_id' => $batch->id,
                                'email_sent' => now(),
                                'confirmed' => now(),
                                'session' => $session->id,
                            ]
                        );

                        // Remove from waitlist if exists
                        AdmissionWaitlist::where('user_id', $user->userId)->delete();

                        return response()->json([
                            'status' => 'success',
                            'message' => 'Booking successful.',
                        ], 201);

            }
        } else {
            $session = MasterSession::find($validated['session_id']);
            if (!$session) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The selected session id is invalid.',
                    'errors' => ['session_id' => ['The selected session id is invalid.']],
                ], 422);
            }
        }

        try {
            $bookingService->book($user, $course, $batch, $session);
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
            'message' => 'Booking successful.',
        ], 201);
    }

    public function destroy(Request $request, Booking $booking, BookingService $bookingService): JsonResponse
    {
        $user = $request->user();
        if (! $user || $booking->user_id !== $user->userId) {
            return response()->json(['status' => 'error', 'message' => 'Forbidden.'], 403);
        }

        $slotRestored = $bookingService->cancel($booking);

        return response()->json([
            'status' => 'success',
            'slot_restored' => $slotRestored,
        ]);
    }

    public function mine(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $bookings = Booking::query()->where('status', true)
            ->where('user_id', $user->userId)
            ->with('programmeBatch.centre', 'programmeBatch.programme', 'courseSession', 'course')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $bookings,
        ]);
    }
}
