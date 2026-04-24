<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\ProgrammeBatch;
use App\Services\InPersonEnrollmentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InPersonAvailabilityController extends Controller
{
    private const REMAINING_UNLIMITED = 999999;

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
    public function batches(Request $request, InPersonEnrollmentService $enrollmentService): JsonResponse
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
        $admissionBatch = Batch::where('status', true)
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

        $centreSessions = CourseSession::query()
            ->where('course_id', $course->id)
            ->where('centre_id', $course->centre_id)
            ->where('session_type', CourseSession::TYPE_CENTRE)
            ->where('status', true)
            // Lexicographic time string is imperfect but stable; JS re-sorts by parsed clock time.
            ->orderByRaw("COALESCE(NULLIF(TRIM(course_time), ''), 'ZZZ')")
            ->orderBy('id')
            ->get();

        $batchData = $batches->values()->map(function ($batch, $index) use ($centreSessions, $enrollmentService) {
            $sessionData = $centreSessions->map(function (CourseSession $cs) use ($batch, $enrollmentService) {
                $enrolled = $enrollmentService->enrolledCount($batch->id, $cs->id);
                $limit = $cs->limit;
                $hasLimit = $limit !== null && (int) $limit > 0;
                $showSeatCount = $hasLimit;
                $remaining = $hasLimit ? max(0, (int) $limit - $enrolled) : self::REMAINING_UNLIMITED;

                return [
                    'session_id' => $cs->id,
                    'course_session_id' => $cs->id,
                    'session_name' => $cs->name ?: ($cs->session ? "{$cs->session} Session" : 'Session'),
                    'time' => $cs->course_time ?: '',
                    'remaining' => $remaining,
                    'show_seat_count' => $showSeatCount,
                    'limit' => $hasLimit ? (int) $limit : null,
                ];
            })->values()->toArray();

            return [
                'id' => $batch->id,
                'batch' => 'Cohort '.($index + 1),
                'start_date' => $batch->start_date->format('Y-m-d'),
                'end_date' => $batch->end_date->format('Y-m-d'),
                'sessions' => $sessionData,
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
}
