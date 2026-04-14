<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Centre;
use App\Models\Course;
use App\Models\MasterSession;
use App\Models\Programme;
use App\Models\ProgrammeBatch;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function index(Request $request, AvailabilityService $availabilityService): JsonResponse
    {
        $request->validate([
            'centre_id' => 'required|integer|exists:centres,id',
            'course_id' => 'required|integer|exists:courses,id',
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $centreId = (int) $request->query('centre_id');
        $courseId = (int) $request->query('course_id');
        $from = Carbon::parse($request->query('from'));
        $to = Carbon::parse($request->query('to'));

        $result = $availabilityService->getAvailableSlots($centreId, $courseId, $from, $to);

        return response()->json($result);
    }

    /**
     * List active ProgrammeBatches for a course + centre, each enriched
     * with per-session remaining seats from the occupancy engine.
     *
     * GET /api/availability/batches?course_id=X&centre_id=Y
     */
    public function batches(Request $request, BookingService $bookingService): JsonResponse
    {
        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            // 'centre_id' => 'required|integer|exists:centres,id',
        ]);

        $courseId = (int) $request->query('course_id');
        // $centreId = (int) $request->query('centre_id');

        $course = Course::with(['programme', 'centre'])->find($courseId);
        $centre = $course->centre;

        if (!$course || !$course->programme || !$centre) {
            return response()->json(['success' => false, 'message' => 'Course or centre not found.'], 404);
        }

        if ((int) $course->centre_id !== $centre->id) {
            return response()->json(['success' => false, 'message' => 'Course does not belong to this centre.'], 422);
        }

        $programme = $course->programme;
        $courseType = $programme->courseType();

        // Find the current active admission batch
        $today = Carbon::today();
        $admissionBatch = Batch::where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where('status', true)
            ->where('completed', false)
            ->first();

        if (!$admissionBatch) {
            return response()->json([
                'success' => true,
                'centre' => ['id' => $centre->id, 'title' => $centre->title],
                'course_type' => $courseType,
                'capacity' => $centre->slotCapacityFor($courseType),
                'batches' => [],
            ]);
        }

        // Find active programme batches
        $batches = ProgrammeBatch::where('admission_batch_id', $admissionBatch->id)
            ->where('programme_id', $programme->id)
            ->where('status', true)
            ->orderBy('start_date')
            ->get();

        // Get active master sessions for this course type
        $sessions = MasterSession::where('course_type', $courseType)
            ->where('status', true)
            ->get();

        $batchData = $batches->map(function ($batch) use ($course, $sessions, $bookingService) {
            $sessionData = $sessions->map(function ($session) use ($course, $batch, $bookingService) {
                return [
                    'session_id' => $session->id,
                    'session_name' => $session->master_name,
                    'time' => $session->time,
                    'remaining' => $bookingService->getRemainingSeats($course->centre->id, $batch->id, $session->id),
                ];
            })->values()->toArray();

            return [
                'id' => $batch->id,
                'start_date' => $batch->start_date->format('Y-m-d'),
                'end_date' => $batch->end_date->format('Y-m-d'),
                'sessions' => $sessionData,
            ];
        })->values()->toArray();

        return response()->json([
            'success' => true,
            'centre' => ['id' => $centre->id, 'title' => $centre->title],
            'course_type' => $courseType,
            'capacity' => $centre->slotCapacityFor($courseType),
            'batches' => $batchData,
        ]);
    }

    /**
     * Find other centres in the same branch that offer the same programme,
     * with their available slots — part of the recommendation system.
     *
     * GET /api/availability/sibling-centres?course_id=X&centre_id=Y
     */
    public function siblingCentres(Request $request, BookingService $bookingService): JsonResponse
    {
        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'centre_id' => 'required|integer|exists:centres,id',
        ]);

        $courseId = (int) $request->query('course_id');
        $centreId = (int) $request->query('centre_id');

        $course = Course::with('programme')->find($courseId);
        $centre = Centre::find($centreId);

        if (!$course || !$course->programme || !$centre || !$centre->branch_id) {
            return response()->json(['success' => false, 'message' => 'Course or centre not found.'], 404);
        }

        $programme = $course->programme;
        $courseType = $programme->courseType();

        // Find the current active admission batch
        $today = Carbon::today();
        $admissionBatch = Batch::where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where('status', true)
            ->where('completed', false)
            ->first();

        if (!$admissionBatch) {
            return response()->json([
                'success' => true,
                'origin_centre' => ['id' => $centre->id, 'title' => $centre->title],
                'programme' => ['id' => $programme->id, 'title' => $programme->title],
                'alternatives' => [],
            ]);
        }

        // Find sibling centres: same branch, different centre, active
        $siblingCentres = Centre::where('branch_id', $centre->branch_id)
            ->where('id', '!=', $centreId)
            ->where('status', true)
            ->get();

        // Get active master sessions for this course type
        $sessions = MasterSession::where('course_type', $courseType)
            ->where('status', true)
            ->get();

        $alternatives = [];

        foreach ($siblingCentres as $siblingCentre) {
            // Find a course for the same programme at this sibling centre in the current batch
            $siblingCourse = Course::where('centre_id', $siblingCentre->id)
                ->where('programme_id', $programme->id)
                ->where('batch_id', $admissionBatch->id)
                ->where('status', true)
                ->first();

            if (!$siblingCourse) {
                continue;
            }

            // Find programme batches for this sibling
            $batches = ProgrammeBatch::where('admission_batch_id', $admissionBatch->id)
                ->where('programme_id', $programme->id)
                ->where('status', true)
                ->orderBy('start_date')
                ->get();

            $totalAvailable = 0;

            $batchData = $batches->map(function ($batch) use ($siblingCentre, $sessions, $bookingService, &$totalAvailable) {
                $sessionData = $sessions->map(function ($session) use ($siblingCentre, $batch, $bookingService, &$totalAvailable) {
                    $remaining = $bookingService->getRemainingSeats($siblingCentre->id, $batch->id, $session->id);
                    $totalAvailable += $remaining;

                    return [
                        'session_id' => $session->id,
                        'session_name' => $session->master_name,
                        'time' => $session->time,
                        'remaining' => $remaining,
                    ];
                })->values()->toArray();

                return [
                    'id' => $batch->id,
                    'start_date' => $batch->start_date->format('Y-m-d'),
                    'end_date' => $batch->end_date->format('Y-m-d'),
                    'sessions' => $sessionData,
                ];
            })->values()->toArray();

            // Only include centres that actually have availability
            if ($totalAvailable > 0) {
                $alternatives[] = [
                    'centre_id' => $siblingCentre->id,
                    'centre_name' => $siblingCentre->title,
                    'course_id' => $siblingCourse->id,
                    'gps_location' => $siblingCentre->gps_location ?? [],
                    'available' => $totalAvailable,
                    'batches' => $batchData,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'origin_centre' => ['id' => $centre->id, 'title' => $centre->title],
            'programme' => ['id' => $programme->id, 'title' => $programme->title],
            'alternatives' => $alternatives,
        ]);
    }
}
