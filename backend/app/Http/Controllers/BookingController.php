<?php

namespace App\Http\Controllers;

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

class BookingController extends Controller
{
    public function store(
        Request $request,
        BookingService $bookingService,
        AvailabilityService $availabilityService,
        GhanaCardService $ghanaCardService
    ): JsonResponse {
        $validated = $request->validate([
            'programme_batch_id' => 'required|integer|exists:programme_batches,id',
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        $batch = ProgrammeBatch::find($validated['programme_batch_id']);
        if (! $batch->status) {
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
        if (! $course || ! $course->programme) {
            return response()->json([
                'status' => 'error',
                'message' => 'Course not found.',
            ], 404);
        }

        if (! $request->filled('session_id') && $request->filled('course_session_id')) {
            $request->merge(['session_id' => (int) $request->input('course_session_id')]);
        }

        $programme = $course->programme;
        $selfPace = $request->boolean('self_pace') || $request->boolean('self-pace');
        // Hybrid "online" programmes can have centre timetable rows; study-from-home uses master sessions + self_pace.
        $usesCentreSessionBooking = $course->isInPersonProgramme()
            && ! ($course->isOnlineProgramme() && $selfPace);

        $validated = array_merge($validated, $request->validate([
            'session_id' => (! $usesCentreSessionBooking && $selfPace) ? 'nullable|integer' : 'required|integer',
        ]));

        if ($course->programme_id !== $batch->programme_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Course does not belong to this programme batch.',
            ], 422);
        }

        // Fetch session: centre timetable row vs master (online) session
        if ($usesCentreSessionBooking) {
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
            $sessionId = $validated['session_id'] ?? null;
            if ($selfPace && ! $sessionId) {
                $courseType = $programme->courseType();
                $session = MasterSession::query()
                    ->where('course_type', $courseType)
                    ->where('status', true)
                    ->orderBy('id')
                    ->first();
            } else {
                $session = MasterSession::find($sessionId);
            }
            if (! $session) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The selected session id is invalid.',
                    'errors' => ['session_id' => ['The selected session id is invalid.']],
                ], 422);
            }
        }

        try {
            $booking = $bookingService->book(
                $user,
                $course,
                $batch,
                $session,
                $usesCentreSessionBooking ? false : $selfPace
            );
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
            'redirect_url' => url('/student/dashboard'),
            // 'data' => $booking->load('programmeBatch', 'courseSession', 'course'),
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
