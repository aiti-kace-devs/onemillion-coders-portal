<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\MasterSession;
use App\Models\ProgrammeBatch;
use App\Models\UserAdmission;
use App\Models\AdmissionWaitlist;
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
        $isSelfPaced = $request->query('self-paced') === 'true';
        $withSupport = $request->query('with-support') === 'true';

        // Adjust validation based on self-paced flag
        $validationRules = [
            'programme_batch_id' => 'required|integer|exists:programme_batches,id',
            'course_id' => 'required|integer|exists:courses,id',
            'session_id' => $isSelfPaced ? 'nullable|integer' : 'required|integer',
        ];

        $validated = $request->validate($validationRules);

        $batch = ProgrammeBatch::find($validated['programme_batch_id']);
        if (!$batch || !$batch->status) {
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
        if (!$course) {
            return response()->json([
                'status' => 'error',
                'message' => 'Course not found.',
            ], 404);
        }

        $programme = $course->programme;
        if (!$programme) {
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

        // Handle self-paced enrollment (no session required)
        if ($isSelfPaced) {
            return $this->handleSelfPacedEnrollment($user, $course, $batch);
        }

        // Resolve and validate session based on delivery mode
        $session = $this->resolveSession($course, $isInPerson, $validated['session_id']);
        if (!$session) {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected session id is invalid.',
                'errors' => ['session_id' => ['The selected session id is invalid.']],
            ], 422);
        }

        // Delegate ALL booking logic to BookingService (fixes execution flow + slot counting)
        try {
            $bookingService->book($user, $course, $batch, $session, $isInPerson);
        } catch (Exception $e) {
            Log::error('Booking failed', [
                'user_id' => $user->userId,
                'course_id' => $course->id,
                'batch_id' => $batch->id,
                'session_id' => $session->id,
                'is_in_person' => $isInPerson,
                'error' => $e->getMessage(),
            ]);

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

    /**
     * Handle self-paced enrollment (no session booking required)
     */
    protected function handleSelfPacedEnrollment($user, Course $course, ProgrammeBatch $batch): JsonResponse
    {
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
                'session' => null,
            ]
        );

        AdmissionWaitlist::where('user_id', $user->userId)->delete();

        NotificationController::notify(
            $user->id,
            'COURSE_SELECTION',
            'Enrollment Confirmed',
            'You have successfully enrolled in <strong>' . e($course->course_name) . '</strong> (self-paced). Thank you for enrolling.'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Self-paced enrollment successful.',
        ], 201);
    }

    /**
     * Resolve the appropriate session model based on course delivery mode
     */
    protected function resolveSession(Course $course, bool $isInPerson, int $sessionId): CourseSession|MasterSession|null
    {
        if ($isInPerson) {
            $session = CourseSession::find($sessionId);
            // Validate that session belongs to this course
            return ($session && $session->course_id === $course->id) ? $session : null;
        }

        return MasterSession::find($sessionId);
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