<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\MasterSession;
use App\Models\ProgrammeBatch;
use App\Models\User;
use App\Models\UserAdmission;
use App\Models\AdmissionWaitlist;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\GhanaCardService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function store(
        Request $request,
        BookingService $bookingService,
        AvailabilityService $availabilityService,
        GhanaCardService $ghanaCardService
    ): JsonResponse
    {
        // Check if this is a self-paced enrollment. Accept the historical
        // kebab-case flag and the snake/camel variants used by older clients.
        $selfPacedFlag = $request->query(
            'self-paced',
            $request->query('self_pace', $request->query('selfPace', false))
        );
        $isSelfPaced = filter_var($selfPacedFlag, FILTER_VALIDATE_BOOLEAN);

        // Adjust validation based on self-paced flag
        $validationRules = [
            'programme_batch_id' => 'required|integer|exists:programme_batches,id',
            'course_id' => 'required|integer|exists:courses,id',
            'session_id' => $isSelfPaced ? 'nullable|integer' : 'required|integer',
            'is_protocol' => 'sometimes|boolean',
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

        if ($isSelfPaced && $isInPerson) {
            return response()->json([
                'status' => 'error',
                'message' => 'In-person programmes require centre session enrollment.',
            ], 422);
        }

        // Handle self-paced enrollment (no session required)
        if ($isSelfPaced) {
            $user->registered_course = $course->id;
            $user->shortlist = true;
            $user->support = false;
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

            NotificationController::notify(
                $user->id,
                'COURSE_SELECTION',
                'Enrollment Confirmed',
                'You have successfully enrolled in <strong>' . e($course->course_name) . '</strong> (self-paced). You will be notified of next steps.'
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Self-paced enrollment successful.',
            ], 201);
        }

        // Fetch session for non-self-paced enrollments
        if ($isInPerson) {
            $session = CourseSession::find($validated['session_id']);
            if (! $session
                || (int) $session->course_id !== (int) $course->id
                || (int) $session->centre_id !== (int) $course->centre_id
                || $session->session_type !== CourseSession::TYPE_CENTRE) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The selected session id is invalid.',
                    'errors' => ['session_id' => ['The selected session id is invalid.']],
                ], 422);
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

        $isProtocolBooking = (bool) ($user->is_protocol ?? false);

        try {
            $bookingService->book($user, $course, $batch, $session, $isProtocolBooking);
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

        if (! $isInPerson) {
            $user->support = true;
            $user->save();
        }

        NotificationController::notify(
            $user->id,
            'COURSE_SELECTION',
            'Enrollment Confirmed',
            'You have successfully enrolled in <strong>' . e($course->course_name) . '</strong>. You will be notified of next steps.'
        );

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
