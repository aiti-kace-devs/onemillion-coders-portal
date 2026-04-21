<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Branch;
use App\Models\Centre;
use App\Models\Constituency;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseSession;
use App\Models\District;
use App\Models\MasterSession;
use App\Models\Programme;
use App\Models\ProgrammeBatch;
use App\Models\UserAdmission;
use App\Services\BookingService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class CourseProgrammeController extends Controller
{
    public function index(Request $request)
    {
        $query = Programme::with(['category', 'courseCertification', 'courseModules'])
            ->withCount('courseModules');

        if ($request->has('filter')) {
            $filter = $request->filter;
            $today = Carbon::today();

            if ($filter === 'running') {
                $query->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today);
            }

            if ($filter === 'coming_soon') {
                $query->where('start_date', '>', $today);
            }
        }

        $programmes = $query->get();

        return response()->json([
            'success' => true,
            'data' => $programmes,
        ]);
    }

    public function programmeWithBatch(Request $request)
    {
        $validated = $request->validate([
            'centre_id' => 'nullable|integer|exists:centres,id',
            'mode' => 'nullable|string',
        ]);

        $filter = $request->query('filter');
        $sort = $request->query('sort');
        $order = strtolower((string) $request->query('order', 'asc'));
        $limit = $request->query('limit');
        $centreId = isset($validated['centre_id']) ? (int) $validated['centre_id'] : null;
        $deliveryMode = $this->normalizeProgrammeDeliveryMode($validated['mode'] ?? null);
        $resolvedFilter = $filter ?: ($centreId !== null ? 'ongoing' : null);

        if (($validated['mode'] ?? null) !== null && $deliveryMode === null) {
            throw ValidationException::withMessages([
                'mode' => ['The selected mode is invalid. Use Online or In Person.'],
            ]);
        }

        if (is_string($sort) && str_starts_with($sort, '-')) {
            $sort = ltrim($sort, '-');
            $order = 'desc';
        }

        $limit = is_numeric($limit) ? (int) $limit : null;
        if ($limit !== null && $limit <= 0) {
            $limit = null;
        }

        $cacheKey = 'programme_with_batch:'.($resolvedFilter ? (string) $resolvedFilter : 'all')
            .':sort:'.($sort ? (string) $sort : 'none')
            .':order:'.($order === 'desc' ? 'desc' : 'asc')
            .':limit:'.($limit !== null ? (string) $limit : 'none')
            .':centre:'.($centreId !== null ? (string) $centreId : 'all')
            .':mode:'.($deliveryMode ?? 'all');
        $courseMatchApiController = app(CourseMatchAPIController::class);

        $programmes = Cache::remember($cacheKey, 600, function () use ($request, $resolvedFilter, $sort, $order, $limit, $centreId, $deliveryMode, $courseMatchApiController) {
            $courses = $this->getProgrammeWithBatchCourses($resolvedFilter, $centreId, $deliveryMode);

            $items = $courses->unique('programme_id')
                ->map(function ($course) use ($request, $courseMatchApiController) {
                    $courseId = $course->id ? (int) $course->id : null;
                    $slotLeftResponse = $courseId ? $courseMatchApiController->courseSlotLeft($request, $courseId) : null;
                    $slotLeft = $slotLeftResponse ? $slotLeftResponse->getData(true)['slot_left'] : null;

                    return $this->formatProgrammeWithBatchItem($course, $slotLeft);
                })
                ->filter()
                ->values();

            if ($sort) {
                $allowedSorts = [
                    'id',
                    'title',
                    'duration',
                    'sub_title',
                    'level',
                    'mode_of_delivery',
                    'course_id',
                    'centre_id',
                ];

                if (in_array($sort, $allowedSorts, true)) {
                    $descending = $order === 'desc';
                    $items = $items->sortBy($sort, SORT_NATURAL | SORT_FLAG_CASE, $descending)->values();
                }
            }

            if ($limit !== null) {
                $items = $items->take($limit)->values();
            }

            return $items;
        });

        return response()->json([
            'success' => true,
            'data' => $programmes,
        ]);
    }

    protected function getProgrammeWithBatchCourses(?string $filter = null, ?int $centreId = null, ?string $deliveryMode = null)
    {
        $batchIds = $this->getProgrammeWithBatchBatchIds($filter);

        $query = Course::whereIn('batch_id', $batchIds)
            ->whereNotNull('programme_id')
            ->where('status', true)
            ->with(['programme.category', 'programme.coverImage', 'programme.courseCertification', 'programme.courseModules']);

        if ($centreId !== null) {
            $query->where('centre_id', $centreId);
        }

        if ($deliveryMode !== null) {
            $deliveryModeValues = $this->getProgrammeDeliveryModeValues($deliveryMode);

            $query->whereHas('programme', function ($programmeQuery) use ($deliveryModeValues) {
                $programmeQuery->where(function ($modeQuery) use ($deliveryModeValues) {
                    foreach ($deliveryModeValues as $index => $mode) {
                        $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                        $modeQuery->{$method}('LOWER(mode_of_delivery) = ?', [$mode]);
                    }
                });
            });
        }

        return $query->get();
    }

    protected function normalizeProgrammeDeliveryMode(?string $mode): ?string
    {
        if ($mode === null) {
            return null;
        }

        $normalized = preg_replace('/[^a-z]/', '', strtolower(trim($mode)));

        return match ($normalized) {
            'online', 'onilne' => 'online',
            'inperson' => 'in-person',
            default => null,
        };
    }

    /**
     * @return array<int, string>
     */
    protected function getProgrammeDeliveryModeValues(string $deliveryMode): array
    {
        return match ($deliveryMode) {
            'online' => ['online', 'onilne'],
            'in-person' => ['in person'],
            default => [],
        };
    }

    protected function getProgrammeWithBatchBatchIds(?string $filter = null)
    {
        $today = Carbon::today()->toDateString();
        $query = Batch::query()->where('status', true);

        if ($filter === 'passed') {
            return $query->where(function ($batchQuery) use ($today) {
                $batchQuery->where('end_date', '<', $today)
                    ->orWhere('completed', true);
            })->pluck('id');
        }

        $query->where('completed', false);

        if ($filter === 'ongoing') {
            $query->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today);
        }

        if ($filter === 'upcoming') {
            $query->where('start_date', '>', $today);
        }

        return $query->pluck('id');
    }

    protected function formatProgrammeWithBatchItem(Course $course, ?int $slotLeft = null): ?array
    {
        $programme = $course->programme;

        if (! $programme) {
            return null;
        }

        return [
            'id' => $programme->id,
            'title' => $programme->title,
            'duration' => $course->duration ?? $programme->duration,
            // 'status' => $course->status ?? $programme->status ?? true,
            'description' => $programme->description,
            'sub_title' => $programme->sub_title,
            'level' => $programme->level,
            'mode_of_delivery' => $programme->mode_of_delivery,
            // 'provider' => $programme->provider,
            'job_responsible' => $programme->job_responsible,
            'image' => $programme->image,
            'category' => $programme->category
                ? [
                    'id' => $programme->category->id,
                    'title' => $programme->category->title,
                ]
                : null,
            'course_certification' => $programme->courseCertification
                ? $programme->courseCertification
                    ->map(fn ($cert) => [
                        'title' => $cert->title,
                        'description' => $cert->description,
                        'type' => $cert->type,
                        'status' => $cert->status,
                    ])
                    ->values()
                : [],
            'course_id' => $course->id,
            'slot_left' => 0, // Default to 0 if slot left is not available
            // 'slot_left' => $slotLeft,
            'centre_id' => $course->centre_id,
        ];
    }

    // public function programmeWithBatch(Request $request)
    // {
    //     $query = Batch::with([
    //             'assignedCourseBatches.programme' => function ($q) {
    //                 $q->with(['category', 'courseCertification', 'courseModules'])
    //                 ->withCount('courseModules');
    //             }
    //         ])
    //         ->whereHas('assignedCourseBatches.programme');

    //     if ($request->has('filter')) {
    //         $filter = $request->filter;
    //         $today = Carbon::today();

    //         if ($filter === 'running') {
    //             $query->where('start_date', '<=', $today)
    //                 ->where('end_date', '>=', $today);
    //         }

    //         if ($filter === 'coming_soon') {
    //             $query->where('start_date', '>', $today);
    //         }
    //     }

    //     $batches = $query->get()
    //         ->map(function ($batch) {
    //             return [
    //                 'id'          => $batch->id,
    //                 'title'       => $batch->title,
    //                 'description' => $batch->description,
    //                 'start_date'  => $batch->start_date,
    //                 'end_date'    => $batch->end_date,
    //                 'programmes'  => $batch->assignedCourseBatches
    //                                     ->pluck('programme')
    //                                     ->unique('id')
    //                                     ->values(),
    //             ];
    //         });

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $batches
    //     ]);
    // }

    public function allBatches(Request $request)
    {
        $query = Batch::query();

        // $query->with([
        //     'assignedCourseBatches.programme.category',
        //     'assignedCourseBatches.programme.courseCertification',
        //     'assignedCourseBatches.programme.courseModules'
        // ]);

        // Optional filtering
        if ($request->has('filter')) {
            $filter = $request->filter;
            $today = Carbon::today();

            if ($filter === 'running') {
                $query->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today);
            }

            if ($filter === 'coming_soon') {
                $query->where('start_date', '>', $today);
            }

            if ($filter === 'completed') {
                $query->where('end_date', '<', $today);
            }
        }

        $query->orderBy('start_date', 'asc');

        $batches = $query->get();

        return response()->json([
            'success' => true,
            'data' => $batches,
        ]);
    }

    public function programmesByBatch($batchId, Request $request)
    {
        $query = Batch::with([
            'courses.programme' => function ($q) {
                $q->with(['category', 'courseCertification', 'courseModules'])
                    ->withCount('courseModules');
            },
        ])
            ->whereHas('courses.programme');

        // Apply optional filters
        if ($request->has('filter')) {
            $filter = $request->filter;
            $today = Carbon::today();

            if ($filter === 'running') {
                $query->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today);
            }

            if ($filter === 'coming_soon') {
                $query->where('start_date', '>', $today);
            }
        }

        $batch = $query->findOrFail($batchId);

        $data = [
            'id' => $batch->id,
            'title' => $batch->title,
            'description' => $batch->description,
            'start_date' => $batch->start_date,
            'end_date' => $batch->end_date,
            'programmes' => $batch->courses
                ->pluck('programme')
                ->unique('id')
                ->values(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function show($id)
    {
        $programme = Programme::with(['category', 'courseCertification', 'courseModules'])
            ->withCount('courseModules')
            ->find($id);

        if (! $programme) {
            return response()->json([
                'success' => false,
                'message' => 'Programme not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $programme,
        ]);
    }

    public function programmesByCategory($categoryId)
    {
        $programmes = Programme::where('course_category_id', $categoryId)
            ->with(['category', 'courseModules', 'courseCertification'])
            ->withCount('courseModules')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $programmes,
        ]);
    }

    public function getCourseCategory()
    {
        $courseCategory = Cache::remember('course_categories', 600, function () {
            return CourseCategory::where('status', 1)
                ->orderBy('title')
                ->get(['id', 'title', 'description', 'status', 'icon']);
        });

        return response()->json([
            'success' => true,
            'data' => $courseCategory,
        ]);
    }




    public function getBranch(Request $request)
{
    $addCentreCount = filter_var($request->query('add_centre_count', false), FILTER_VALIDATE_BOOLEAN);
    $programmeId = $request->query('programme_id') ? (int) $request->query('programme_id') : null;

    $cacheKey = 'branches:'
        .($addCentreCount ? 'with_centres' : 'basic')
        .($programmeId ? ':programme_'.$programmeId : '');

    $branch = Cache::remember($cacheKey, 600, function () use ($addCentreCount, $programmeId) {
        $branches = Branch::where('status', 1)->orderBy('title')->get(['id', 'title', 'status']);

        return $branches->map(function ($branch) use ($addCentreCount, $programmeId) {
            $payload = [
                'id' => $branch->id,
                'title' => $branch->title,
                'status' => $branch->status,
            ];

            if ($addCentreCount) {
                // Get all ready centres for this branch
                $centres = Centre::where('branch_id', $branch->id)
                    ->where('status', 1)
                    ->where('is_ready', 1)
                    ->with(['courses' => function ($query) use ($programmeId) {
                        $query->where('status', true);
                        if ($programmeId) {
                            $query->where('programme_id', $programmeId);
                        }
                    }])
                    ->get();

                // Count centres with at least one course that has available slots
                $count = 0;
                
                foreach ($centres as $centre) {
                    foreach ($centre->courses as $course) {
                        if ($this->courseHasAvailableSlots($course, $programmeId)) {
                            $count++;
                            break; // Count centre only once
                        }
                    }
                }
                
                $payload['total_centres'] = $count;
            }

            return $payload;
        })->values();
    });

    return response()->json([
        'success' => true,
        'data' => $branch,
    ]);
}

/**
 * Check if a course has at least one session with available slots
 */
protected function courseHasAvailableSlots($course, $programmeId = null): bool
{
    if (!$programmeId) {
        // No programme filter: just check if course has any sessions
        return $course->sessions()->where('status', true)->exists()
            || $course->programme?->isInPerson() === false; // Online courses assumed available
    }

    $programme = $course->programme;
    if (!$programme || $programme->id != $programmeId) {
        return false;
    }

    $isInPerson = $programme->isInPerson();
    
    // Find active admission batch
    $today = \Carbon\Carbon::today();
    $admissionBatch = Batch::where('start_date', '<=', $today)
        ->where('end_date', '>=', $today)
        ->where('status', true)
        ->where('completed', false)
        ->first();

    if (!$admissionBatch) {
        return false;
    }

    // Get programme batches
    $programmeBatches = ProgrammeBatch::where('admission_batch_id', $admissionBatch->id)
        ->where('programme_id', $programmeId)
        ->where('status', true)
        ->pluck('id')
        ->toArray();

    if (empty($programmeBatches)) {
        return false;
    }

    if ($isInPerson) {
        // IN-PERSON: Check sessions
        $sessions = $course->sessions()->where('status', true)->get();
        
        foreach ($sessions as $session) {
            $limit = $session->limit ?? 0;
            if ($limit <= 0) continue;

            // Count bookings for this session across all batches
            $booked = UserAdmission::where('course_id', $course->id)
                ->where('session', $session->id)
                ->whereIn('programme_batch_id', $programmeBatches)
                ->count();

            if ($booked < $limit) {
                return true; // Found an available slot
            }
        }
        return false;
    } else {
        // ONLINE: Check centre capacity vs bookings
        $centre = $course->centre;
        if (!$centre) return false;

        $timeAllocation = $programme->time_allocation;
        $capacity = 0;

        if ($timeAllocation == Programme::TIME_ALLOCATION_SHORT) {
            $capacity = (int) ($centre->short_slots_per_day ?? 0);
        } elseif ($timeAllocation == Programme::TIME_ALLOCATION_LONG) {
            $capacity = (int) ($centre->long_slots_per_day ?? 0);
        }

        if ($capacity <= 0) {
            return false;
        }

        // Count total bookings for this programme at this centre
        $booked = UserAdmission::whereHas('programmeBatch', function ($q) use ($programmeId) {
                $q->where('programme_id', $programmeId);
            })
            ->whereIn('programme_batch_id', $programmeBatches)
            ->whereHas('course', function ($q) use ($centre) {
                $q->where('centre_id', $centre->id);
            })
            ->count();

        return $booked < $capacity;
    }
}








    public function getBranchSummary()
    {
        $today = Carbon::today()->toDateString();
        $branches = Branch::where('status', 1)
            ->get()
            ->map(function ($branch) use ($today) {
                $centres = Centre::where('branch_id', $branch->id)
                    ->where('status', 1)
                    ->where('is_ready', 1)
                    ->get();

                $courses = Course::join('centres', 'courses.centre_id', '=', 'centres.id')
                    ->join('admission_batches', 'courses.batch_id', '=', 'admission_batches.id')
                    ->where('centres.branch_id', $branch->id)
                    ->whereNotNull('courses.programme_id')
                    ->where('courses.status', 1)
                    ->where('admission_batches.start_date', '<=', $today)
                    ->where('admission_batches.end_date', '>=', $today)
                    ->where('admission_batches.completed', false)
                    ->where('admission_batches.status', true)
                    ->get(['courses.id', 'courses.programme_id', 'courses.centre_id']);

                $programmeIds = $courses->pluck('programme_id')->unique()->values();
                $programmes = Programme::whereIn('id', $programmeIds)->get(['title', 'sub_title']);
                $courseIds = $courses->pluck('id');
                $admittedUsersCount = UserAdmission::whereIn('course_id', $courseIds)
                    ->whereNotNull('confirmed')
                    ->count();

                return [
                    'branch_id' => $branch->id,
                    'branch_title' => $branch->title,
                    'total_centres' => $centres->count(),
                    'total_courses' => $courses->count(),
                    'total_trained_coders' => $admittedUsersCount,
                    'centres' => $centres
                        ->map(fn ($centre) => [
                            'id' => $centre->id,
                            'title' => $centre->title,
                            'gps_location' => $centre->gps_location ?? [],
                        ])
                        ->values(),
                    'courses' => $programmes,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $branches,
        ]);
    }

    public function programmeLocations(Programme $programme)
    {
        $centres = $programme->centre()->with('branch')->get();

        $groupedByBranch = $centres->groupBy('branch.id');

        $regions = $groupedByBranch->map(function ($centres, $branchId) {
            $branch = $centres->first()->branch;

            $cleanCentres = $centres->map(function ($centre) {
                $centreData = $centre->toArray();
                unset($centreData['branch']);
                $centreData['gps_location'] = $centre->gps_location ?? [];

                return $centreData;
            })->values();

            return [
                'id' => $branch->id,
                'title' => $branch->title,
                'status' => $branch->status,
                'created_at' => $branch->created_at,
                'updated_at' => $branch->updated_at,
                'centres' => $cleanCentres,
            ];
        })->values();

        return response()->json([
            'programme' => $programme->title,
            'regions' => $regions,
        ]);
    }

    public function programmesByCentre(Centre $centre)
    {
        $programmes = $this->getProgrammeWithBatchCourses('ongoing', $centre->id)
            ->unique('programme_id')
            ->map(fn ($course) => $this->formatProgrammeWithBatchItem($course))
            ->filter()
            ->values();

        return response()->json([
            'centre_id' => $centre->id,
            'centre' => $centre->title,
            'gps_location' => $centre->gps_location ?? [],
            'programmes' => $programmes,
        ]);
    }

    public function centreById(Centre $centre)
    {
        $centreData = $centre->toArray();
        $centreData['gps_location'] = $centre->gps_location ?? [];

        return response()->json([
            'success' => true,
            'data' => $centreData,
        ]);
    }

    public function centresByBranch(Branch $branch)
    {
        $centres = $branch->centre()
            ->where('status', 1)
            ->get()
            ->map(function ($centre) {
                $centreData = $centre->toArray();
                $centreData['gps_location'] = $centre->gps_location ?? [];

                return $centreData;
            })
            ->values();

        return response()->json([
            'region' => $branch->title,
            'centres' => $centres,
        ]);
    }

public function districtsByBranch(Request $request)
{
    $data = $request->validate([
        'branch_id' => 'required|integer|exists:branches,id',
        'programme_id' => 'nullable|integer|exists:programmes,id',
    ]);

    $branch = Branch::query()->findOrFail($data['branch_id']);
    $programmeId = $data['programme_id'] ?? null;

    $addCentreCount = filter_var($request->query('add_centre_count', false), FILTER_VALIDATE_BOOLEAN);
    
    // Build cache key based on parameters
    $cacheKey = 'districts_by_branch:'.$branch->id
        .':'.($addCentreCount ? 'with_centres' : 'basic')
        .':has_centres'
        .($programmeId ? ':programme_'.$programmeId : '');

    $districts = Cache::flexible($cacheKey, cache_flexible_ttl(), function () use ($branch, $addCentreCount, $programmeId) {
        $districtQuery = District::query()
            ->where('branch_id', $branch->id)
            ->where('status', 1)
            ->whereHas('centres')
            ->orderBy('title');

        if ($addCentreCount) {
            $districtQuery->withCount('centres');
        }

        return $districtQuery->get(['id', 'title'])
            ->map(function ($district) use ($addCentreCount, $programmeId) {
                $payload = [
                    'id' => $district->id,
                    'title' => $district->title,
                ];

                if ($addCentreCount) {
                    // Get all ready centres for this district
                    $centres = $district->centres()
                        ->where('status', 1)
                        ->where('is_ready', 1)
                        ->with(['courses' => function ($query) use ($programmeId) {
                            $query->where('status', true);
                            if ($programmeId) {
                                $query->where('programme_id', $programmeId);
                            }
                        }])
                        ->get();

                    // Count centres with at least one course that has available slots
                    $count = 0;
                    
                    foreach ($centres as $centre) {
                        foreach ($centre->courses as $course) {
                            if ($this->courseHasAvailableSlots($course, $programmeId)) {
                                $count++;
                                break; // Count centre only once
                            }
                        }
                    }
                    
                    $payload['total_centres'] = $count;
                } else {
                    // When not adding centre count, default to 0 for filtering
                    $payload['total_centres'] = 0;
                }

                return $payload;
            })
            // FILTER: Only keep districts with at least one available centre
            ->filter(function ($district) use ($addCentreCount) {
                if (!$addCentreCount) {
                    // If not counting centres, return all districts (original behavior)
                    return true;
                }
                // Only include districts with at least one available centre
                return ($district['total_centres'] ?? 0) > 0;
            })
            ->values(); // Re-index array after filtering
    });

    return response()->json([
        'success' => true,
        'branch_id' => $branch->id,
        'branch' => $branch->title,
        'districts' => $districts,
    ]);
}


    

    public function constituencyByRegion(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
        ]);

        $branch = Branch::query()->findOrFail($data['branch_id']);

        $addCentreCount = filter_var($request->query('add_centre_count', false), FILTER_VALIDATE_BOOLEAN);
        $cacheKey = 'constituencies_by_branch:'.$branch->id.':'.($addCentreCount ? 'with_centres' : 'basic');

        $constituencies = Cache::remember($cacheKey, 600, function () use ($branch, $addCentreCount) {
            $constituencyQuery = Constituency::query()
                ->where('branch_id', $branch->id)
                ->orderBy('title');

            if ($addCentreCount) {
                $constituencyQuery->withCount('centres');
            }

            return $constituencyQuery->get(['id', 'title'])
                ->map(function ($constituency) use ($addCentreCount) {
                    $payload = [
                        'id' => $constituency->id,
                        'title' => $constituency->title,
                    ];

                    if ($addCentreCount) {
                        $payload['total_centres'] = (int) $constituency->centres_count;
                    }

                    return $payload;
                })
                ->values();
        });

        return response()->json([
            'success' => true,
            'branch_id' => $branch->id,
            'branch' => $branch->title,
            'constituencies' => $constituencies,
        ]);
    }

    public function centresByDistrict(Request $request)
    {
        $data = $request->validate([
            'district_id' => 'required|integer|exists:districts,id',
        ]);

        $district = District::query()
            ->with(['centres' => function ($query) {
                $query->where('status', 1)
                    ->withCount('courses')
                    ->having('courses_count', '>', 0)
                    ->orderBy('title');
            }])
            ->findOrFail($data['district_id']);

        return response()->json([
            'success' => true,
            // 'district_id' => $district->id,
            'district' => $district->title,
            'centres' => $district->centres
                ->map(function ($centre) {
                    return [
                        'id' => $centre->id,
                        'title' => $centre->title,
                        'is_ready' => $centre->is_ready,
                        'is_pwd_friendly' => $centre->is_pwd_friendly,
                        'images' => $centre->images ?? [],
                        'video' => $centre->video,
                        'has_courses' => $centre->courses_count > 0,
                        'courses_count' => $centre->courses_count,
                    ];
                })
                ->values(),
        ]);
    }

    public function getTotalCentresCount()
    {
        $totalCentres = Centre::where('status', 1)
            ->where('is_ready', 1)
            ->count();

        return response()->json([
            'success' => true,
            'total_centres' => $totalCentres,
        ]);
    }




    
public function availabilityPerCentre($programmeId, Request $request, BookingService $bookingService)
{
    $request->validate([
        'district_id' => 'required|integer|exists:districts,id',
        'sort' => 'nullable|string|in:centre_name,capacity,availability',
        'order' => 'nullable|string|in:asc,desc',
        'filter' => 'nullable|string|in:has_availability',
        'min_availability' => 'nullable|integer|min:0',
        'limit' => 'nullable|integer|min:1',
    ]);

    $districtId = (int) $request->query('district_id');
    $sort = $request->query('sort', 'centre_name');
    $order = strtolower($request->query('order', 'asc'));
    $filter = $request->query('filter');
    $minAvailability = (int) ($request->query('min_availability', 0));
    $limit = $request->query('limit') ? (int) $request->query('limit') : null;

    $cacheKey = 'programme_availability:'.($programmeId ?? 'none').':district:'.($districtId ?? 'none')
        .':sort:'.($sort ?? 'none').':order:'.($order ?? 'asc')
        .':filter:'.($filter ?? 'none').':min_avail:'.($minAvailability ?? 'none')
        .':limit:'.($limit ?? 'none');

    $response = Cache::remember($cacheKey, 600, function () use ($programmeId, $districtId, $bookingService, $sort, $order, $filter, $minAvailability, $limit) {
        $programme = Programme::findOrFail($programmeId);
        $courseType = $programme->courseType();
        $isInPerson = $programme->isInPerson();

        // Find the current active admission batch
        $today = Carbon::today();
        $admissionBatch = Batch::where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where('status', true)
            ->where('completed', false)
            ->first();

        if (!$admissionBatch) {
            return ['success' => true, 'available_centres' => []];
        }

        // Get programme batches for this programme
        $batches = ProgrammeBatch::where('admission_batch_id', $admissionBatch->id)
            ->where('programme_id', $programmeId)
            ->where('status', true)
            ->orderBy('start_date')
            ->get();

        if ($batches->isEmpty()) {
            return ['success' => true, 'available_centres' => []];
        }

        // Get active master sessions for this course type for non in-person programmes
        $sessions = collect();
        if (! $isInPerson) {
            $sessions = MasterSession::where('course_type', $courseType)
                ->where('status', true)
                ->where('session_type', '!=', 'Online')
                ->get();
            $sessions = $this->sortMasterSessions($sessions);

            if ($sessions->isEmpty()) {
                return ['success' => true, 'available_centres' => []];
            }
        }

        // Get centres in the specified district that offer this programme
        $centres = Centre::whereHas('districts', function ($query) use ($districtId) {
            $query->where('district_id', $districtId);
        })
            ->whereHas('courses', function ($query) use ($programmeId, $admissionBatch) {
                $query->where('programme_id', $programmeId)
                    ->where('batch_id', $admissionBatch->id)
                    ->where('status', true);
            })
            ->with([
                'branch:id,title',
                'districts:id,title',
                'courses' => function ($query) use ($programmeId, $admissionBatch) {
                    $query->where('programme_id', $programmeId)
                        ->where('batch_id', $admissionBatch->id)
                        ->where('status', true)
                        ->select(['id', 'centre_id', 'programme_id', 'batch_id']);
                },
            ])
            ->where('status', true)
            ->get();

        $availableCentres = [];

        foreach ($centres as $centre) {
            $centreSessions = $sessions;
            $remainingSeats = [];
            $centreCapacity = $centre->slotCapacityFor($courseType);

            if ($isInPerson) {
                // IN-PERSON: Per-cohort, per-session capacity
                $centreCourse = $centre->courses->first();
                if (! $centreCourse) {
                    continue;
                }

                $centreSessions = CourseSession::where('course_id', $centreCourse->id)
                    ->where('status', true)
                    ->get();
                $centreSessions = $this->sortMasterSessions($centreSessions);
                if ($centreSessions->isEmpty()) {
                    continue;
                }

                $centreCapacity = (int) $centreSessions->sum('limit');
            } else {
                // ONLINE: Per-cohort capacity (NOT shared across cohorts)
                // Determine capacity based on programme's time_allocation
                $timeAllocation = $programme->time_allocation;
                
                if ($timeAllocation == Programme::TIME_ALLOCATION_SHORT) {
                    $centreCapacity = (int) ($centre->short_slots_per_day ?? 0);
                } elseif ($timeAllocation == Programme::TIME_ALLOCATION_LONG) {
                    $centreCapacity = (int) ($centre->long_slots_per_day ?? 0);
                } else {
                    // Fallback to slotCapacityFor if time_allocation is unexpected
                    $centreCapacity = (int) ($centre->slotCapacityFor($courseType) ?? 0);
                }

                //  Calculate remaining per cohort per session (INDEPENDENT for each cohort)
                $batchIds = $batches->pluck('id')->toArray();
                $sessionIds = $centreSessions->pluck('id')->toArray();
                
                // Count UserAdmission grouped by programme_batch_id AND session
                $bookedPerBatchSession = UserAdmission::select(
                        'programme_batch_id',
                        'session', 
                        DB::raw('COUNT(*) as count')
                    )
                    ->whereHas('programmeBatch', function ($query) use ($programmeId) {
                        $query->where('programme_id', $programmeId);
                    })
                    ->whereIn('programme_batch_id', $batchIds)
                    ->whereHas('course', function ($query) use ($centre) {
                        $query->where('centre_id', $centre->id);
                    })
                    ->whereIn('session', $sessionIds)
                    ->groupBy('programme_batch_id', 'session')
                    ->get()
                    ->pluck('count', function ($row) {
                        return (string) "{$row->programme_batch_id}:{$row->session}";
                    })
                    ->toArray();

                // Calculate remaining for EACH cohort+session combination
                foreach ($batches as $batch) {
                    foreach ($centreSessions as $session) {
                        $key = (string) "{$batch->id}:{$session->id}";
                        $bookedCount = $bookedPerBatchSession[$key] ?? 0;
                        $remainingSeats[$key] = max(0, $centreCapacity - $bookedCount);
                    }
                }
            }

            // Pre-fetch booked counts for in-person sessions (per-cohort)
            $inPersonBookedCounts = [];
            if ($isInPerson && $centreSessions->isNotEmpty()) {
                $centreCourse = $centre->courses->first();
                if ($centreCourse) {
                    $sessionIds = $centreSessions->pluck('id')->toArray();
                    $batchIds = $batches->pluck('id')->toArray();
                    
                    $booked = UserAdmission::select(
                            'programme_batch_id', 
                            'session', 
                            DB::raw('COUNT(*) as count')
                        )
                        ->where('course_id', $centreCourse->id)
                        ->whereIn('session', $sessionIds)
                        ->whereIn('programme_batch_id', $batchIds)
                        ->groupBy('programme_batch_id', 'session')
                        ->get()
                        ->pluck('count', function ($row) {
                            return (string) "{$row->programme_batch_id}:{$row->session}";
                        })
                        ->toArray();
                    
                    $inPersonBookedCounts = $booked;
                }
            }

            $totalAvailable = 0;

            $batchData = $batches->values()->map(function ($batch, $index) use (
                $centreSessions, 
                $remainingSeats, 
                $isInPerson, 
                $inPersonBookedCounts,
                $centreCapacity,
                &$totalAvailable
            ) {
                $sessionData = $centreSessions->map(function ($session) use (
                    $batch, 
                    $remainingSeats, 
                    $isInPerson, 
                    $inPersonBookedCounts,
                    $centreCapacity,
                    &$totalAvailable
                ) {
                    $key = (string) "{$batch->id}:{$session->id}";
                    
                    if ($isInPerson) {
                        //  IN-PERSON: Per-cohort capacity (limit - booked for this cohort+session)
                        $limit = $session->limit ?? 0;
                        $bookedCount = $inPersonBookedCounts[$key] ?? 0;
                        $remaining = max(0, $limit - $bookedCount);
                    } else {
                        //  ONLINE: Per-cohort capacity (same as in-person logic)
                        $remaining = $remainingSeats[$key] ?? 0;
                    }
                    
                    $totalAvailable += $remaining;

                    return [
                        'session_name' => $isInPerson
                            ? ($session->session ?? 'Unknown')
                            : "{$session->session_type} Session",
                        'time' => $session->time ?? $session->course_time ?? optional($session->masterSession)->time,
                        'remaining' => $remaining,
                        'limit' => $isInPerson ? ($session->limit ?? 0) : null,
                        'booked' => $isInPerson ? ($inPersonBookedCounts[$key] ?? 0) : null,
                        'centre_capacity' => ! $isInPerson ? $centreCapacity : null,
                    ];
                })->values()->toArray();

                return [
                    'batch' => 'Cohort ' . ($index + 1),
                    'start_date' => $batch->start_date->format('Y-m-d'),
                    'end_date' => $batch->end_date->format('Y-m-d'),
                    'sessions' => $sessionData,
                ];
            })->values()->toArray();

            // Only include centres with available seats
            if ($totalAvailable > 0) {
                $primaryDistrict = $centre->districts->first();
                $availableCentres[] = [
                    'branch_name' => $centre->branch?->title,
                    'district_name' => $primaryDistrict?->title,
                    'centre_name' => $centre->title,
                    'capacity' => $centreCapacity,
                    'total_availability' => $totalAvailable,
                    'batches' => $batchData,
                ];
            }
        }

        // Apply filtering
        if ($filter === 'has_availability' || $minAvailability > 0) {
            $availableCentres = array_filter($availableCentres, function ($centre) use ($minAvailability) {
                return $centre['total_availability'] >= $minAvailability;
            });
        }

        // Apply sorting
        usort($availableCentres, function ($a, $b) use ($sort, $order) {
            $aVal = $bVal = null;
            switch ($sort) {
                case 'centre_name':
                    $aVal = strtolower($a['centre_name']);
                    $bVal = strtolower($b['centre_name']);
                    break;
                case 'capacity':
                    $aVal = (int) $a['capacity'];
                    $bVal = (int) $b['capacity'];
                    break;
                case 'availability':
                    $aVal = (int) $a['total_availability'];
                    $bVal = (int) $b['total_availability'];
                    break;
                default:
                    $aVal = strtolower($a['centre_name']);
                    $bVal = strtolower($b['centre_name']);
            }
            if ($aVal === $bVal) {
                return 0;
            }
            $result = ($aVal < $bVal) ? -1 : 1;
            return $order === 'desc' ? -$result : $result;
        });

        // Apply limit
        if ($limit !== null && $limit > 0) {
            $availableCentres = array_slice($availableCentres, 0, $limit);
        }

        return ['success' => true, 'available_centres' => $availableCentres];
    });

    return response()->json($response);
}




    protected function sortMasterSessions($sessions)
    {
        return collect($sessions)
            ->sortBy(function ($session) {
                $sessionType = $session->session_type ?? $session->session ?? optional($session->masterSession)->session_type ?? null;
                $time = $session->time ?? $session->course_time ?? optional($session->masterSession)->time ?? null;

                return [
                    $this->sessionTypePriority($sessionType),
                    $this->sessionStartMinutes($time),
                    strtolower(trim((string) ($time ?? ''))),
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
