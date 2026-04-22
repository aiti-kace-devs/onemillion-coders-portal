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
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function store(
        Request $request,
        BookingService $bookingService,
        AvailabilityService $availabilityService,
        GhanaCardService $ghanaCardService
    ): JsonResponse {
        $selfPacedFlag = $request->query(
            'self-paced',
            $request->query('self_pace', $request->query('selfPace', false))
        );
        $isSelfPaced = filter_var($selfPacedFlag, FILTER_VALIDATE_BOOLEAN);

        $validationRules = [
            'programme_batch_id' => 'required|integer|exists:programme_batches,id',
            'course_id' => 'required|integer|exists:courses,id',
            'session_id' => $isSelfPaced ? 'nullable|integer' : 'required|integer',
            'is_protocol' => 'sometimes|boolean',
            'capacity_pool' => 'nullable|string|in:reserved,standard',
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

        if ((int) $course->programme_id !== (int) $batch->programme_id) {
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

        if ($isSelfPaced) {
            return $this->handleSelfPacedEnrollment($user, $course, $batch);
        }

        $session = $this->resolveSession($course, $isInPerson, (int) $validated['session_id']);
        if (! $session) {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected session id is invalid.',
                'errors' => ['session_id' => ['The selected session id is invalid.']],
            ], 422);
        }

        $forProtocol = (bool) ($user->is_protocol ?? false);
        $capacityPool = $validated['capacity_pool'] ?? null;

        try {
            $bookingService->book($user, $course, $batch, $session, $forProtocol, $capacityPool);
        } catch (Exception $e) {
            Log::error('Booking failed', [
                'user_id' => $user->userId,
                'course_id' => $course->id,
                'batch_id' => $batch->id,
                'session_id' => $session->id,
                'is_in_person' => $isInPerson,
                'for_protocol' => $forProtocol,
                'capacity_pool' => $capacityPool,
                'error' => $e->getMessage(),
            ]);

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

        return response()->json([
            'status' => 'success',
            'message' => 'Booking successful.',
        ], 201);
    }

    protected function handleSelfPacedEnrollment($user, Course $course, ProgrammeBatch $batch): JsonResponse
    {
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
                'session' => null,
            ]
        );

        AdmissionWaitlist::where('user_id', $user->userId)->delete();

        NotificationController::notify(
            $user->id,
            'COURSE_SELECTION',
            'Enrollment Confirmed',
            'You have successfully enrolled in <strong>'.e($course->course_name).'</strong> (self-paced). You will be notified of next steps.'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Self-paced enrollment successful.',
        ], 201);
    }

    protected function resolveSession(Course $course, bool $isInPerson, int $sessionId): CourseSession|MasterSession|null
    {
        if ($isInPerson) {
            $session = CourseSession::where('id', $sessionId)
                ->where('status', true)
                ->first();

            if (! $session
                || (int) $session->centre_id !== (int) $course->centre_id
                || $session->session_type !== CourseSession::TYPE_CENTRE
                || ($session->course_id !== null && (int) $session->course_id !== (int) $course->id)) {
                return MasterSession::where('id', $sessionId)
                    ->where('status', true)
                    ->where('course_type', $course->programme?->courseType())
                    ->where('session_type', '!=', 'Online')
                    ->first();
            }

            return $session;
        }

        return MasterSession::where('id', $sessionId)
            ->where('status', true)
            ->where('course_type', $course->programme?->courseType())
            ->where('session_type', '!=', 'Online')
            ->first();
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
