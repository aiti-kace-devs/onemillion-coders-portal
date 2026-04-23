<?php

namespace App\Http\Controllers;

use App\Jobs\HandleEnrollmentJob;
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
use ReflectionMethod;
use ReflectionException;

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

        if ((int) $course->programme_id !== (int) $batch->programme_id) {
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

        $forProtocol = array_key_exists('is_protocol', $validated)
            ? (bool) $validated['is_protocol']
            : (bool) ($user->is_protocol ?? false);
        $capacityPool = $validated['capacity_pool'] ?? null;

        $enrollmentJob = new HandleEnrollmentJob(
            $user,
            $course,
            $batch,
            $session?->id,
            $isSelfPaced,
            $withSupport,
            $isInPerson,
            $forProtocol,
            $capacityPool
        );

        try {
            if ($session) {
                $bookingService->validateBookingAvailability(
                    $user,
                    $course,
                    $batch,
                    $session,
                    $isInPerson,
                    $forProtocol,
                    $capacityPool
                );
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
                'for_protocol' => $forProtocol,
                'capacity_pool' => $capacityPool,
                'error' => $e->getMessage(),
            ]);

            $recommendations = [];
            if ($session) {
                $recommendations = $this->getAvailableSlotsWithProtocolFallback(
                    $availabilityService,
                    (int) $course->centre_id,
                    (int) $course->id,
                    Carbon::parse($batch->start_date),
                    Carbon::parse($batch->end_date),
                    $forProtocol
                );
            }

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'recommendations' => $recommendations['recommendations'] ?? [],
            ], 409);
        }

        // Partner Integration
        app(\App\Services\PartnerAdmissionService::class)->handleEnrollment($user, $programme);

        return response()->json([
            'status' => 'success',
            'message' => 'Booking successful.',
        ], 201);
    

    }




    protected function getAvailableSlotsWithProtocolFallback(
            AvailabilityService $availabilityService,
            int $centreId,
            int $courseId,
            Carbon $startDate,
            Carbon $endDate,
            bool $forProtocol
        ): array {
            try {
                $method = new ReflectionMethod($availabilityService, 'getAvailableSlots');
                if ($method->getNumberOfParameters() >= 5) {
                    return $availabilityService->getAvailableSlots($centreId, $courseId, $startDate, $endDate, $forProtocol);
                }
            } catch (ReflectionException $e) {
                Log::warning('Unable to inspect getAvailableSlots signature', [
                    'error' => $e->getMessage(),
                ]);
            }

            return $availabilityService->getAvailableSlots($centreId, $courseId, $startDate, $endDate);
        }


    protected function resolveSession(Course $course, bool $isInPerson, int $sessionId): CourseSession|MasterSession|null
    {
        if ($isInPerson) {
            $session = CourseSession::find($sessionId);

            return ($session && $session->course_id === $course->id) ? $session : null;
        }

        return MasterSession::where('id', $sessionId)
            ->where('status', true)
            ->where('course_type', $course->programme?->courseType())
            ->where('session_type', '!=', 'Online')
            ->first();
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
