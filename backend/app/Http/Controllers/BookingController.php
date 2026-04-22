<?php

namespace App\Http\Controllers;

use App\Jobs\HandleEnrollmentJob;
use App\Models\Booking;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\MasterSession;
use App\Models\ProgrammeBatch;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\GhanaCardService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function store(
        Request $request,
        BookingService $bookingService,
        AvailabilityService $availabilityService,
        GhanaCardService $ghanaCardService
    ): JsonResponse {
        $isSelfPaced = filter_var($request->query('self-paced', 'false'), FILTER_VALIDATE_BOOLEAN);
        $withSupport = filter_var($request->query('with-support', 'false'), FILTER_VALIDATE_BOOLEAN);

        $validationRules = [
            'programme_batch_id' => 'required|integer|exists:programme_batches,id',
            'course_id' => 'required|integer|exists:courses,id',
            'session_id' => $isSelfPaced ? 'nullable|integer' : 'required|integer',
        ];

        $validated = $request->validate($validationRules);

        $batch = ProgrammeBatch::find($validated['programme_batch_id']);
        if (! $batch || ! $batch->status) {
            return response()->json([
                'status' => 'error',
                'message' => 'Programme batch is not active.',
            ], 422);
        }

        $user = $request->user();
        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
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
        if (! $course) {
            return response()->json([
                'status' => 'error',
                'message' => 'Course not found.',
            ], 404);
        }

        $programme = $course->programme;
        if (! $programme) {
            return response()->json([
                'status' => 'error',
                'message' => 'Programme not found for this course.',
            ], 404);
        }

        $isInPerson = strtolower(trim((string) $programme->mode_of_delivery)) === 'in person';

        if ($course->programme_id !== $batch->programme_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Course does not belong to this programme batch.',
            ], 422);
        }

        if ($withSupport) {
            $centreIsReady = (bool) ($course->centre?->is_ready);

            if (! $centreIsReady) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Resource (internet & laptop) support is not available for the selected centre at this time. You can try again later',
                ], 422);
            }
        }

        $session = null;
        if (! $isSelfPaced) {
            $session = $this->resolveSession($course, $isInPerson, (int) $validated['session_id']);

            if (! $session) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The selected session id is invalid.',
                    'errors' => ['session_id' => ['The selected session id is invalid.']],
                ], 422);
            }
        }

        $enrollmentJob = new HandleEnrollmentJob(
            $user,
            $course,
            $batch,
            $session?->id,
            $isSelfPaced,
            $withSupport,
            $isInPerson
        );

        try {
            if ($session) {
                $bookingService->validateBookingAvailability($user, $course, $batch, $session, $isInPerson);
            }

            $enrollmentJob->handle();
        } catch (Exception $e) {
            Log::error('Enrollment failed', [
                'user_id' => $user->userId,
                'course_id' => $course->id,
                'batch_id' => $batch->id,
                'session_id' => $session?->id,
                'is_self_paced' => $isSelfPaced,
                'with_support' => $withSupport,
                'is_in_person' => $isInPerson,
                'error' => $e->getMessage(),
            ]);

            $recommendations = [];
            if ($session) {
                $recommendations = $availabilityService->getAvailableSlots(
                    $course->centre_id,
                    $course->id,
                    Carbon::parse($batch->start_date),
                    Carbon::parse($batch->end_date)
                );
            }

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'recommendations' => $recommendations['recommendations'] ?? [],
            ], 409);
        }

        $responseUser = $isInPerson ? null : $enrollmentJob->enrolledUser;

        Log::info('Enrollment handled successfully', [
            'Enrollment User in BookingController' => $enrollmentJob->enrolledUser,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $this->resolveSuccessMessage($isSelfPaced, $withSupport, $isInPerson),
            'data' => [
                'booking' => $enrollmentJob->booking,
                'user' => $responseUser,
                'admission' => $enrollmentJob->admission,
                'enrollment' => [
                    'is_self_paced' => $isSelfPaced,
                    'with_support' => $withSupport,
                    'is_in_person' => $isInPerson,
                ],
            ],
        ], 201);
    }

    /**
     * Resolve the appropriate session model based on course delivery mode
     */
    protected function resolveSession(Course $course, bool $isInPerson, int $sessionId): CourseSession|MasterSession|null
    {
        if ($isInPerson) {
            $session = CourseSession::find($sessionId);

            return ($session && $session->course_id === $course->id) ? $session : null;
        }

        return MasterSession::find($sessionId);
    }

    protected function resolveSuccessMessage(bool $isSelfPaced, bool $withSupport, bool $isInPerson): string
    {
        if ($isSelfPaced) {
            return 'Self-paced enrollment successful.';
        }

        if ($withSupport) {
            return 'With-support enrollment successful.';
        }

        if ($isInPerson) {
            return 'In-person enrollment successful.';
        }

        return 'Enrollment successful.';
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

        $bookings = Booking::query()
            ->where('status', true)
            ->where('user_id', $user->userId)
            ->with('programmeBatch.centre', 'programmeBatch.programme', 'courseSession', 'masterSession', 'course')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $bookings,
        ]);
    }
}
