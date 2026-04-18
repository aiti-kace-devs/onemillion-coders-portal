<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Centre;
use App\Models\Course;
use App\Models\CourseSession;
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
     * POST /api/availability/batches { "course_id": X, "centre_id": Y }
     */
    public function batches(Request $request, BookingService $bookingService): JsonResponse
    {
        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        $courseId = (int) $request->input('course_id');

        $course = Course::with(['programme.courseCertification', 'centre.branch', 'centre.districts'])->find($courseId);

        if (! $course || ! $course->programme || ! $course->centre) {
            return response()->json(['success' => false, 'message' => 'Course or centre not found.'], 404);
        }

        $centre = $course->centre;
        $programme = $course->programme;
        $courseType = $programme->courseType();
        $isInPerson = strtolower(trim((string) $programme->mode_of_delivery)) === 'in person';

        $regionName = $centre->branch?->title;
        $districtName = $centre->districts->first()?->title;
        $certificateTitle = $programme->courseCertification->first()?->title;

        // Find the current active admission batch
        $today = Carbon::today();
        $admissionBatch = Batch::where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where('status', true)
            ->where('completed', false)
            ->first();

        if (! $admissionBatch) {
            return response()->json([
                'success' => true,
                'centre' => ['id' => $centre->id, 'title' => $centre->title],
                'course_type' => $courseType,
                'capacity' => $isInPerson ? null : $centre->slotCapacityFor($courseType),
                'region_name' => $regionName,
                'district_name' => $districtName,
                'certificate_title' => $certificateTitle,
                'batches' => [],
            ]);
        }

        // Find active programme batches with eager loading
        $batches = ProgrammeBatch::where('admission_batch_id', $admissionBatch->id)
            ->where('programme_id', $programme->id)
            ->where('status', true)
            ->orderBy('start_date')
            ->get();

        if ($batches->isEmpty()) {
            return response()->json([
                'success' => true,
                'centre' => ['id' => $centre->id, 'title' => $centre->title],
                'course_type' => $courseType,
                'capacity' => $isInPerson ? null : $centre->slotCapacityFor($courseType),
                'region_name' => $regionName,
                'district_name' => $districtName,
                'certificate_title' => $certificateTitle,
                'batches' => [],
            ]);
        }

        // Get active sessions for this course type or centre-specific for in-person
        if ($isInPerson) {
            $sessions = CourseSession::where('course_id', $courseId)
                ->where('status', true)
                ->get();
        } else {
            $sessions = MasterSession::where('course_type', $courseType)
                ->where('status', true)
                ->get();
        }
        $sessions = $this->sortMasterSessions($sessions);

        // Calculate capacity
        $capacity = $isInPerson ? $sessions->sum('limit') : $centre->slotCapacityFor($courseType);

        // Batch fetch all remaining seats at once (only for master sessions)
        $remainingSeats = [];
        if (! $isInPerson) {
            $remainingSeats = $bookingService->getRemainingSeatsBatch(
                $centre->id,
                $batches->pluck('id')->toArray(),
                $sessions->pluck('id')->toArray()
            );
        }

        $batchData = $batches->values()->map(function ($batch, $index) use ($sessions, $remainingSeats, $isInPerson) {
            $sessionData = $sessions->map(function ($session) use ($batch, $remainingSeats, $isInPerson) {
                $key = "{$batch->id}:{$session->id}";

                return [
                    'session_id' => $session->id,
                    'session_name' => $isInPerson ? ($session->session ?? 'Unknown') : ($session->session_type ?? $session->name ?? optional($session->masterSession)->session_type ?? 'Unknown Session'),
                    'time' => $session->time ?? $session->course_time ?? optional($session->masterSession)->time ?? 'Unknown',
                    'remaining' => $isInPerson ? ($session->limit ?? 0) : ($remainingSeats[$key] ?? 0),
                ];
            })->values()->toArray();

            return [
                'id' => $batch->id,
                'batch' => 'Cohort '.($index + 1),
                'start_date' => $batch->start_date->format('Y-m-d'),
                'end_date' => $batch->end_date->format('Y-m-d'),
                'sessions' => $sessionData,
            ];
        })->values()->toArray();

        return response()->json([
            'success' => true,
            'centre' => ['id' => $centre->id, 'title' => $centre->title],
            'course_type' => $courseType,
            'capacity' => $isInPerson ? null : $capacity,
            'region_name' => $regionName,
            'district_name' => $districtName,
            'certificate_title' => $certificateTitle,
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

        $filter = $request->query('filter');
        $sort = $request->query('sort');
        $order = strtolower((string) $request->query('order', 'asc'));
        $limit = $request->query('limit');

        if (is_string($sort) && str_starts_with($sort, '-')) {
            $sort = ltrim($sort, '-');
            $order = 'desc';
        }

        $limit = is_numeric($limit) ? (int) $limit : null;
        if ($limit !== null && $limit <= 0) {
            $limit = null;
        }

        $course = Course::with('programme')->find($courseId);
        $centre = Centre::with([
            'branch:id,title',
            'districts:id,title',
        ])->find($centreId);

        if (! $course || ! $course->programme || ! $centre || ! $centre->branch_id) {
            return response()->json(['success' => false, 'message' => 'Course or centre not found.'], 404);
        }

        $programme = $course->programme;
        $courseType = $programme->courseType();

        $programmePayload = [
            'id' => $programme->id,
            'title' => $programme->title,
            'duration' => $programme->duration,
            'duration_in_days' => $programme->duration_in_days,
            'prerequisites' => $programme->prerequisites,
            'certificate' => optional($programme->courseCertification->first())->title,
        ];

        // Find the current active admission batch
        $today = Carbon::today();
        $admissionBatch = Batch::where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where('status', true)
            ->where('completed', false)
            ->first();

        if (! $admissionBatch) {
            return response()->json([
                'success' => true,
                'origin_centre' => array_merge(
                    ['id' => $centre->id, 'title' => $centre->title],
                    $this->centreLocationPayload($centre)
                ),
                'programme' => $programmePayload,
                'alternatives' => [],
            ]);
        }

        // Find sibling centres: same branch, different centre, active
        $siblingCentres = Centre::with([
            'branch:id,title',
            'districts:id,title',
        ])->where('branch_id', $centre->branch_id)
            ->where('id', '!=', $centreId)
            ->where('status', true)
            ->get();

        if ($siblingCentres->isEmpty()) {
            return response()->json([
                'success' => true,
                'origin_centre' => array_merge(
                    ['id' => $centre->id, 'title' => $centre->title],
                    $this->centreLocationPayload($centre)
                ),
                'programme' => $programmePayload,
                'alternatives' => [],
            ]);
        }

        // Get active master sessions for this course type
        $sessions = MasterSession::where('course_type', $courseType)
            ->where('status', true)
            ->get();
        $sessions = $this->sortMasterSessions($sessions);

        if ($sessions->isEmpty()) {
            return response()->json([
                'success' => true,
                'origin_centre' => array_merge(
                    ['id' => $centre->id, 'title' => $centre->title],
                    $this->centreLocationPayload($centre)
                ),
                'programme' => $programmePayload,
                'alternatives' => [],
            ]);
        }

        $alternatives = [];

        foreach ($siblingCentres as $siblingCentre) {
            // Find a course for the same programme at this sibling centre in the current batch
            $siblingCourse = Course::where('centre_id', $siblingCentre->id)
                ->where('programme_id', $programme->id)
                ->where('batch_id', $admissionBatch->id)
                ->where('status', true)
                ->first();

            if (! $siblingCourse) {
                continue;
            }

            // Find programme batches for this sibling
            $batches = ProgrammeBatch::where('admission_batch_id', $admissionBatch->id)
                ->where('programme_id', $programme->id)
                ->where('status', true)
                ->orderBy('start_date')
                ->get();

            if ($batches->isEmpty()) {
                continue;
            }

            // Batch fetch all remaining seats for this centre
            $remainingSeats = $bookingService->getRemainingSeatsBatch(
                $siblingCentre->id,
                $batches->pluck('id')->toArray(),
                $sessions->pluck('id')->toArray()
            );

            $totalAvailable = 0;

            $batchData = $batches->values()->map(function ($batch, $index) use ($sessions, $remainingSeats, &$totalAvailable) {
                $sessionData = $sessions->map(function ($session) use ($batch, $remainingSeats, &$totalAvailable) {
                    $key = "{$batch->id}:{$session->id}";
                    $remaining = $remainingSeats[$key] ?? 0;
                    $totalAvailable += $remaining;

                    return [
                        'session_id' => $session->id,
                        'session_name' => "{$session->session_type} Session",
                        'time' => $session->time,
                        'remaining' => $remaining,
                    ];
                })->values()->toArray();

                return [
                    'id' => $batch->id,
                    'batch' => 'Cohort '.($index + 1),
                    'start_date' => $batch->start_date->format('Y-m-d'),
                    'end_date' => $batch->end_date->format('Y-m-d'),
                    'sessions' => $sessionData,
                ];
            })->values()->toArray();

            // Only include centres that actually have availability
            if ($totalAvailable > 0) {
                $alternatives[] = array_merge([
                    'centre_id' => $siblingCentre->id,
                    'centre_name' => $siblingCentre->title,
                    'is_centre_ready' => $siblingCentre->is_ready,
                    'is_pwd_friendly' => $siblingCentre->is_pwd_friendly,
                    'wheelchair_accessible' => $siblingCentre->wheelchair_accessible,
                    'has_access_ramp' => $siblingCentre->has_access_ramp,
                    'has_accessible_toilet' => $siblingCentre->has_accessible_toilet,
                    'has_elevator' => $siblingCentre->has_elevator,
                    'course_id' => $siblingCourse->id,
                    // 'gps_location' => $siblingCentre->gps_location ?? [],
                    'available' => $totalAvailable,
                    'batches' => $batchData,
                ], $this->centreLocationPayload($siblingCentre));
            }
        }

        // Convert to collection for filtering, sorting, and limiting
        $alternatives = collect($alternatives);

        // Apply filter to alternatives
        if (is_string($filter) && $filter !== '') {
            $filterLower = strtolower($filter);
            $alternatives = $alternatives->filter(function ($alternative) use ($filterLower) {
                return $this->centreMatchesFilter($alternative, $filterLower);
            })->values();
        }

        // Apply sort to alternatives
        if (is_string($sort) && $sort !== '') {
            $alternatives = $this->sortCentres($alternatives, $sort, $order);
        }

        // Apply limit to alternatives
        if ($limit !== null) {
            $alternatives = $alternatives->take($limit)->values();
        }

        return response()->json([
            'success' => true,
            'origin_centre' => array_merge(
                ['id' => $centre->id, 'title' => $centre->title],
                $this->centreLocationPayload($centre)
            ),
            'programme' => $programmePayload,
            'alternatives' => $alternatives->values()->all(),
        ]);
    }

    protected function centreLocationPayload(Centre $centre): array
    {
        $districts = $centre->districts
            ->map(function ($district) {
                return [
                    'title' => $district->title,
                ];
            })
            ->values()
            ->all();

        $primaryDistrict = $centre->districts->first();

        return [
            'branch_name' => $centre->branch?->title,
            'district_name' => $primaryDistrict?->title,
        ];
    }

    /**
     * Check if a centre alternative matches the given filter string.
     * Searches in centre_name.
     */
    protected function centreMatchesFilter(array $alternative, string $filterLower): bool
    {
        $searchableFields = [
            'centre_name',
        ];

        foreach ($searchableFields as $field) {
            $value = $alternative[$field] ?? null;
            if ($value !== null && str_contains(strtolower((string) $value), $filterLower)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sort a collection of centre alternatives by the specified field and order.
     */
    protected function sortCentres($centres, string $sortField, string $order): \Illuminate\Support\Collection
    {
        $sortableFields = [
            'centre_name',
            'available',
            'centre_id',
        ];

        if (! in_array($sortField, $sortableFields, true)) {
            return $centres;
        }

        return $centres->sortBy(function ($centre) use ($sortField) {
            $value = $centre[$sortField] ?? null;
            if ($sortField === 'available') {
                return is_int($value) ? $value : PHP_INT_MAX;
            }

            return strtolower((string) $value);
        }, SORT_NATURAL)->when($order === 'desc', fn ($collection) => $collection->reverse())->values();
    }

    protected function sortMasterSessions($sessions): \Illuminate\Support\Collection
    {
        return collect($sessions)
            ->sortBy(function ($session) {
                // MasterSession: session_type = "Morning", "Afternoon", "Evening"
                // CourseSession: session_type = "course" or "centre", use session column directly
                $sessionType = $session->session_type ?? '';

                // For CourseSession, use the session column which stores the period name
                if (strtolower(trim((string) $sessionType)) === 'course' || strtolower(trim((string) $sessionType)) === 'centre') {
                    $sessionType = $session->session ?? '';
                }

                $time = $session->time ?? $session->course_time ?? optional($session->masterSession)->time ?? '';

                return [
                    $this->sessionTypePriority($sessionType),
                    $this->sessionStartMinutes($time),
                    strtolower(trim((string) $time)),
                    (int) ($session->id ?? 0),
                ];
            }, SORT_REGULAR)
            ->values();
    }

    protected function sessionTypePriority(?string $sessionType): int
    {
        return match (strtolower(trim((string) $sessionType))) {
            'morning' => 0,
            'afternoon' => 1,
            'evening' => 2,
            'fullday' => 3,
            'online' => 4,
            default => 99,
        };
    }

    protected function sessionStartMinutes(?string $time): int
    {
        $startTime = trim(explode('-', (string) $time, 2)[0] ?? '');

        if ($startTime === '') {
            return PHP_INT_MAX;
        }

        $timestamp = strtotime($startTime);

        if ($timestamp === false) {
            return PHP_INT_MAX;
        }

        return ((int) date('G', $timestamp) * 60) + (int) date('i', $timestamp);
    }
}
