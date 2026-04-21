<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Booking;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\ProgrammeBatch;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InPersonAvailabilityController extends Controller
{
    /**
     * GET /api/availability/in-person/batches?course_id=
     *
     * In-person programmes only: cohorts + centre course_sessions and optional seat limits.
     *
     * Programme batches (cohorts) and admission windows come from admin data; each course at a
     * centre must have active {@see CourseSession} rows with session_type "centre" — configure
     * those in the admin panel. This endpoint does not create sessions.
     *
     * Cohort order: start_date asc, then end_date desc when start matches (longer window first), then id.
     * Session order: course_time string (empty last), then id — client also re-sorts via normalizeInPersonBatches().
     */
    public function batches(Request $request, \App\Services\BookingService $bookingService): JsonResponse
    {
        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        $course = Course::with([
            'programme.courseCertification',
            'centre.branch',
            'centre.districts',
        ])->find((int) $request->input('course_id'));

        if (! $course || ! $course->programme || ! $course->centre) {
            return response()->json(['success' => false, 'message' => 'Course or centre not found.'], 404);
        }

        if (! $course->isInPersonProgramme()) {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint is only for in-person courses. Use /api/availability/batches for online programmes.',
            ], 422);
        }

        $centre = $course->centre;
        $programme = $course->programme;
        $courseType = $programme->courseType();

        $today = Carbon::today();
        $admissionBatch = Batch::where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where('status', true)
            ->where('completed', false)
            ->first();

        $regionName = $centre->branch?->title;
        $districtName = $centre->districts->first()?->title;
        $certificateTitle = $programme->courseCertification->first()?->title;

        if (! $admissionBatch) {
            return response()->json([
                'success' => true,
                'centre' => ['id' => $centre->id, 'title' => $centre->title],
                'course_type' => $courseType,
                'capacity' => null,
                'region_name' => $regionName,
                'district_name' => $districtName,
                'certificate_title' => $certificateTitle,
                'batches' => [],
            ]);
        }

        $batches = ProgrammeBatch::query()
            ->where('admission_batch_id', $admissionBatch->id)
            ->where('programme_id', $programme->id)
            ->where('status', true)
            // Must match website/lib/inPersonEnrollmentUi.js normalizeInPersonBatches() cohort ordering.
            ->orderBy('start_date')
            ->orderByDesc('end_date')
            ->orderBy('id')
            ->get();

        $centreSessions = $course->activeInPersonEnrollmentSessions()
            ->sortBy(function ($session) {
                return [
                    $this->sessionStartMinutes($session->time ?? $session->course_time ?? ''),
                    (int) ($session->id ?? 0),
                ];
            }, SORT_REGULAR)
            ->values();

        $forProtocolBooking = $request->query('forProtocolBooking') !== null
            ? filter_var($request->query('forProtocolBooking'), FILTER_VALIDATE_BOOLEAN)
            : (bool) ($request->user()?->is_protocol ?? false);

        $batchData = $batches->values()->map(function ($batch, $index) use ($centreSessions, $bookingService, $centre, $forProtocolBooking, $courseType) {
            $sessionData = $centreSessions->map(function ($cs) use ($batch, $bookingService, $centre, $forProtocolBooking, $courseType) {
                $isCourseSession = $cs instanceof CourseSession;
                $limit = $isCourseSession ? $cs->limit : null;
                $hasLimit = $limit !== null && (int) $limit > 0;

                $breakdown = $bookingService->getRemainingSeatBreakdown($centre->id, $batch->id, $cs->id, $isCourseSession, $courseType);
                $reservedRemaining = (int) $breakdown['reserved_remaining'];
                $standardRemaining = (int) $breakdown['standard_remaining'];
                $remaining = $forProtocolBooking ? $reservedRemaining : $standardRemaining;

                return [
                    'session_id' => $cs->id,
                    'course_session_id' => $isCourseSession ? $cs->id : null,
                    'master_session_id' => $isCourseSession ? ($cs->master_session_id ?? null) : $cs->id,
                    'session_name' => $isCourseSession
                        ? ($cs->name ?: ($cs->session ? "{$cs->session} Session" : 'Session'))
                        : ($cs->master_name ?: "{$cs->session_type} Session"),
                    'time' => $cs->course_time ?? $cs->time ?? '',
                    'remaining' => $remaining,
                    'reserved_remaining' => $reservedRemaining,
                    'standard_remaining' => $standardRemaining,
                    'capacity_pool' => $forProtocolBooking ? Booking::CAPACITY_POOL_RESERVED : Booking::CAPACITY_POOL_STANDARD,
                    'show_seat_count' => $hasLimit,
                    'limit' => $hasLimit ? (int) $limit : null,
                ];
            })->values();

            $reservedPoolHasRoom = $forProtocolBooking
                ? $sessionData->contains(fn ($session) => (int) ($session['remaining'] ?? 0) > 0)
                : false;
            $standardSessionData = $forProtocolBooking && ! $reservedPoolHasRoom
                ? $sessionData
                    ->map(function ($session) {
                        $session['remaining'] = (int) ($session['standard_remaining'] ?? 0);
                        $session['capacity_pool'] = Booking::CAPACITY_POOL_STANDARD;

                        return $session;
                    })
                    ->filter(fn ($session) => (int) ($session['remaining'] ?? 0) > 0)
                    ->values()
                : collect();

            return [
                'id' => $batch->id,
                'batch' => 'Cohort '.($index + 1),
                'start_date' => $batch->start_date->format('Y-m-d'),
                'end_date' => $batch->end_date->format('Y-m-d'),
                'sessions' => $sessionData->values()->toArray(),
                'standard_sessions' => $standardSessionData->toArray(),
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'centre' => ['id' => $centre->id, 'title' => $centre->title],
            'course_type' => $courseType,
            'capacity' => null,
            'region_name' => $regionName,
            'district_name' => $districtName,
            'certificate_title' => $certificateTitle,
            'batches' => $batchData,
        ]);
    }

    private function sessionStartMinutes(?string $time): int
    {
        $startTime = trim(preg_split('/\s*-\s*/', (string) $time, 2)[0] ?? '');
        if ($startTime === '') {
            return PHP_INT_MAX;
        }

        $timestamp = strtotime($startTime);

        return $timestamp === false
            ? PHP_INT_MAX
            : ((int) date('G', $timestamp) * 60) + (int) date('i', $timestamp);
    }
}
