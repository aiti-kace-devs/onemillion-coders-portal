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
use Illuminate\Validation\ValidationException;

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
        $isSelfPaced = filter_var($request->query('self-paced', 'false'), FILTER_VALIDATE_BOOLEAN);
        $withSupport = filter_var($request->query('with-support', 'false'), FILTER_VALIDATE_BOOLEAN);

        // Adjust validation based on self-paced flag
        $validationRules = [
            'programme_batch_id' => 'required|integer|exists:programme_batches,id',
            'course_id' => 'required|integer|exists:courses,id',
            'session_id' => 'nullable|integer',
            'course_session_id' => 'nullable|integer',
            'is_protocol' => 'sometimes|boolean',
            'capacity_pool' => 'nullable|string|in:reserved,standard',
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
        $isInPerson = $course->isInPersonProgramme();

        if ($course->programme_id !== $batch->programme_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Course does not belong to this programme batch.',
            ], 422);
        }

        if ($withSupport && ! (bool) ($course->centre?->is_ready)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Resource (internet & laptop) support is not available for the selected centre at this time. You can try again later',
            ], 422);
        }

        // Handle self-paced enrollment (no session required)
        if ($isSelfPaced) {
            $job = new HandleEnrollmentJob(
                $user,
                $course,
                $batch,
                null,
                true,
                false,
                false,
                (bool) ($validated['is_protocol'] ?? false) || (bool) ($user->is_protocol ?? false),
                $validated['capacity_pool'] ?? null
            );

            $job->handle();

            return response()->json([
                'status' => 'success',
                'message' => 'Self-paced enrollment successful.',
                'redirect_url' => url('/student/dashboard'),
            ], 201);
        }

        // Fetch session for non-self-paced enrollments
        try {
            $requestedSessionId = $this->resolveRequestedSessionId($validated, $isInPerson, $isSelfPaced);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please submit one valid session for this enrollment.',
                'errors' => $e->errors(),
            ], 422);
        }

        $session = $this->resolveSession($course, $isInPerson, $requestedSessionId);
        if (! $session) {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected session id is invalid.',
                'errors' => ['session_id' => ['The selected session id is invalid.']],
            ], 422);
        }

        $isProtocolBooking = (bool) ($validated['is_protocol'] ?? false) || (bool) ($user->is_protocol ?? false);
        $job = new HandleEnrollmentJob(
            $user,
            $course,
            $batch,
            $session->id,
            false,
            $withSupport,
            $isInPerson,
            $isProtocolBooking,
            $validated['capacity_pool'] ?? null
        );

        try {
            $job->handle();
        } catch (Exception $e) {
            $forProtocol = $isProtocolBooking;
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
            'message' => 'Booking successful.',
            'redirect_url' => url('/student/dashboard'),
        ], 201);
    }

    protected function resolveRequestedSessionId(array $validated, bool $isInPerson, bool $isSelfPaced): int
    {
        $sessionId = isset($validated['session_id']) ? (int) $validated['session_id'] : null;
        $courseSessionId = isset($validated['course_session_id']) ? (int) $validated['course_session_id'] : null;

        if ($sessionId !== null && $courseSessionId !== null && $sessionId !== $courseSessionId) {
            throw ValidationException::withMessages([
                'session_id' => ['Please submit only one matching session for this enrollment.'],
                'course_session_id' => ['Please submit only one matching session for this enrollment.'],
            ]);
        }

        if ($isSelfPaced) {
            return 0;
        }

        if ($isInPerson) {
            return (int) ($courseSessionId ?? $sessionId ?? 0);
        }

        return (int) ($sessionId ?? 0);
    }

    protected function resolveSession(Course $course, bool $isInPerson, int $sessionId): CourseSession|MasterSession|null
    {
        if ($sessionId < 1) {
            return null;
        }

        if (! $isInPerson) {
            return MasterSession::find($sessionId);
        }

        $session = CourseSession::find($sessionId);
        if (! $session || ! $session->status) {
            return null;
        }

        if (strtolower(trim((string) ($session->session ?? ''))) === 'online') {
            return null;
        }

        if ((int) $session->course_id !== (int) $course->id) {
            return null;
        }

        if ($session->centre_id !== null && (int) $session->centre_id !== (int) $course->centre_id) {
            return null;
        }

        return $session;
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
