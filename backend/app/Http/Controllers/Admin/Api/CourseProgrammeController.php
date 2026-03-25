<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Programme;
use App\Models\CourseCategory;
use App\Models\Branch;
use App\Models\UserAdmission;
use App\Models\Centre;
use App\Models\District;
use App\Models\Course;
use App\Models\Batch;
use App\Models\Constituency;
use App\Models\CourseBatch;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

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
            'data' => $programmes
        ]);
    }




    public function programmeWithBatch(Request $request)
    {
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

        $cacheKey = 'programme_with_batch:' . ($filter ? (string) $filter : 'all')
            . ':sort:' . ($sort ? (string) $sort : 'none')
            . ':order:' . ($order === 'desc' ? 'desc' : 'asc')
            . ':limit:' . ($limit !== null ? (string) $limit : 'none');

        $programmes = Cache::remember($cacheKey, 600, function () use ($filter, $sort, $order, $limit) {
            $today = Carbon::today();

            $batchQuery = Batch::where('completed', false)
                ->where('status', true);

            if ($filter) {
                if ($filter === 'ongoing') {
                    $batchQuery->where('start_date', '<=', $today)
                        ->where('end_date', '>=', $today);
                }

                if ($filter === 'upcoming') {
                    $batchQuery->where('start_date', '>', $today);
                }

                if ($filter === 'passed') {
                    $batchQuery->where('end_date', '<', $today)
                        ->orWhere('completed', true);
                }
            }

            $batchIds = $batchQuery->pluck('id');

            $courses = Course::whereIn('batch_id', $batchIds)
                ->with(['programme.category', 'programme.coverImage', 'programme.courseCertification', 'programme.courseModules'])
                ->get();

            $items = $courses->unique('programme_id')->map(function ($course) {
                $programme = $course->programme;
                if ($programme) {
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
                            ->map(fn($cert) => [
                                'title' => $cert->title,
                                'description' => $cert->description,
                                'type' => $cert->type,
                                'status' => $cert->status,
                            ])
                            ->values()
                            : [],
                        'course_id' => $course->id,
                        'centre_id' => $course->centre_id,
                    ];
                }
                return null;
            })->filter()->values();

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
            'data' => $programmes
        ]);
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
            'data'    => $batches
        ]);
    }




    public function programmesByBatch($batchId, Request $request)
    {
        $query = Batch::with([
            'courses.programme' => function ($q) {
                $q->with(['category', 'courseCertification', 'courseModules'])
                    ->withCount('courseModules');
            }
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
            'id'          => $batch->id,
            'title'       => $batch->title,
            'description' => $batch->description,
            'start_date'  => $batch->start_date,
            'end_date'    => $batch->end_date,
            'programmes'  => $batch->courses
                ->pluck('programme')
                ->unique('id')
                ->values(),
        ];

        return response()->json([
            'success' => true,
            'data'    => $data
        ]);
    }




    public function show($id)
    {
        $programme = Programme::with(['category', 'courseCertification', 'courseModules'])
            ->withCount('courseModules')
            ->find($id);

        if (!$programme) {
            return response()->json([
                'success' => false,
                'message' => 'Programme not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $programme
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
            'data' => $programmes
        ]);
    }





    public function getCourseCategory()
    {
        $courseCategory = Cache::remember('course_categories', 600, function () {
            return CourseCategory::orderBy('title')
                ->get(['id', 'title', 'description', 'status', 'icon']);
        });

        return response()->json([
            'success' => true,
            'data' => $courseCategory
        ]);
    }


    public function getBranch(Request $request)
    {
        $addCentreCount = filter_var($request->query('add_centre_count', false), FILTER_VALIDATE_BOOLEAN);
        $cacheKey = 'branches:' . ($addCentreCount ? 'with_centres' : 'basic');

        $branch = Cache::remember($cacheKey, 600, function () use ($addCentreCount) {
            $branchQuery = Branch::where('status', 1)->orderBy('title');
            if ($addCentreCount) {
                $branchQuery->withCount('centre');
            }

            return $branchQuery->get(['id', 'title', 'status'])
                ->map(function ($branch) use ($addCentreCount) {
                    $payload = [
                        'id' => $branch->id,
                        'title' => $branch->title,
                        'status' => $branch->status,
                    ];

                    if ($addCentreCount) {
                        $payload['total_centres'] = (int) $branch->centre_count;
                    }

                    return $payload;
                })
                ->values();
        });

        return response()->json([
            'success' => true,
            'data' => $branch
        ]);
    }




    public function getBranchSummary()
    {
        $today = Carbon::today()->toDateString();
        $branches = Branch::withCount('centre')
            ->with(['centre'])
            ->get()
            ->map(function ($branch) use ($today) {

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
                    'total_centres' => $branch->centre_count,
                    'total_courses' => $courses->count(),
                    'total_trained_coders' => $admittedUsersCount,
                    'centres' => $branch->centre
                        ->map(fn($centre) => [
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
            'data' => $branches
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
        $today = Carbon::today();

        $ongoingBatchIds = Batch::where('completed', false)
            ->where('status', true)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->pluck('id');

        $courses = Course::where('centre_id', $centre->id)
            ->whereIn('batch_id', $ongoingBatchIds)
            ->with(['programme.category', 'programme.coverImage'])
            ->get();

        $programmes = $courses->map(function ($course) {
            $programme = $course->programme;
            if ($programme) {
                $programmeData = $programme->toArray();
                $programmeData['course_id'] = $course->id;
                return $programmeData;
            }
            return null;
        })->filter()->values();

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
        ]);

        $branch = Branch::query()->findOrFail($data['branch_id']);

        $addCentreCount = filter_var($request->query('add_centre_count', false), FILTER_VALIDATE_BOOLEAN);
        $cacheKey = 'districts_by_branch:' . $branch->id . ':' . ($addCentreCount ? 'with_centres' : 'basic') . ':has_centres';

        $districts = Cache::flexible($cacheKey, \cache_flexible_ttl(), function () use ($branch, $addCentreCount) {
            $districtQuery = District::query()
                ->where('branch_id', $branch->id)
                ->whereHas('centres')
                ->orderBy('title');

            if ($addCentreCount) {
                $districtQuery->withCount('centres');
            }

            return $districtQuery->get(['id', 'title'])
                ->map(function ($district) use ($addCentreCount) {
                    $payload = [
                        'id' => $district->id,
                        'title' => $district->title,
                    ];

                    if ($addCentreCount) {
                        $payload['total_centres'] = (int) $district->centres_count;
                    }

                    return $payload;
                })
                ->values();
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
        $cacheKey = 'constituencies_by_branch:' . $branch->id . ':' . ($addCentreCount ? 'with_centres' : 'basic');

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
                $query->orderBy('title');
            }])
            ->findOrFail($data['district_id']);

        return response()->json([
            'success' => true,
            'district_id' => $district->id,
            'district' => $district->title,
            'centres' => $district->centres
                ->map(function ($centre) {
                    return [
                        'id' => $centre->id,
                        'title' => $centre->title,
                        'is_pwd_friendly' => $centre->is_pwd_friendly,
                        'status' => $centre->status,
                        'gps_location' => $centre->gps_location ?? [],
                        'gps_address' => $centre->gps_address,
                        'wheelchair_accessible' => $centre->wheelchair_accessible,
                        'has_access_ramp' => $centre->has_access_ramp,
                        'has_accessible_toilet' => $centre->has_accessible_toilet,
                        'has_elevator' => $centre->has_elevator,
                        'supports_hearing_impaired' => $centre->supports_hearing_impaired,
                        'supports_visually_impaired' => $centre->supports_visually_impaired,
                        'staff_trained_for_pwd' => $centre->staff_trained_for_pwd,
                        'accessibility_rating' => $centre->accessibility_rating,
                        'pwd_notes' => $centre->pwd_notes,
                        'images' => $centre->images ?? []
                    ];
                })
                ->values(),
        ]);
    }
}
