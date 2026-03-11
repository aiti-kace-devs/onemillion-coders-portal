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
use App\Models\CourseBatch;
use Illuminate\Http\Request;
use Carbon\Carbon;

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



    // public function programmeWithBatch(Request $request)
    //     {
    //         $query = Batch::with([
    //                 'courseBatches.course.programme' => function ($q) {
    //                     $q->with(['category', 'courseCertification', 'courseModules'])
    //                         ->withCount('courseModules');
    //                 }
    //             ])
    //             ->whereHas('courseBatches');

    //         $today = Carbon::today();

    //         if ($request->has('batch')) {
    //             $filter = $request->filter;

    //             if ($filter === 'ongoing') {
    //                 $query->where('start_date', '<=', $today)
    //                     ->where('end_date', '>=', $today)
    //                     ->where('completed', false)
    //                     ->where('status', true);
    //             }

    //             if ($filter === 'upcoming') {
    //                 $query->where('start_date', '>', $today)
    //                     ->where('completed', false)
    //                     ->where('status', true);
    //             }

    //             if ($filter === 'passed') {
    //                 $query->where('end_date', '<', $today)
    //                     ->orWhere('completed', true);
    //             }
    //         }

    //         $batches = $query->get()
    //             ->map(function ($batch) {
    //                 $courseBatches = $batch->courseBatches->map(function ($cb) {
    //                     return [
    //                         'id' => $cb->id,
    //                         'course_id' => $cb->course_id,
    //                         'batch_id' => $cb->batch_id,
    //                         'duration' => $cb->duration,
    //                         'start_date' => $cb->start_date,
    //                         'end_date' => $cb->end_date,
    //                         'course' => $cb->course ? [
    //                             'id' => $cb->course->id,
    //                             'programme_id' => $cb->course->programme_id,
    //                             'programme' => $cb->course->programme ? [
    //                                 'id' => $cb->course->programme->id,
    //                                 'title' => $cb->course->programme->title,
    //                                 'description' => $cb->course->programme->description,
    //                                 'course_category_id' => $cb->course->programme->course_category_id,
    //                                 'cover_image_id' => $cb->course->programme->cover_image_id,
    //                                 'sub_title' => $cb->course->programme->sub_title,
    //                                 'level' => $cb->course->programme->level,
    //                                 'job_responsible' => $cb->course->programme->job_responsible,
    //                                 'image' => $cb->course->programme->image,
    //                                 'overview' => $cb->course->programme->overview,
    //                                 'prerequisites' => $cb->course->programme->prerequisites,
    //                                 'course_modules_count' => $cb->course->programme->course_modules_count,
    //                                 'category' => $cb->course->programme->category,
    //                                 'courseCertification' => $cb->course->programme->courseCertification,
    //                                 'courseModules' => $cb->course->programme->courseModules,
    //                                 'courseModules_count' => $cb->course->programme->courseModules_count,
    //                             ] : null,
    //                         ] : null,
    //                     ];
    //                 });

    //                 return [
    //                     'id' => $batch->id,
    //                     'title' => $batch->title,
    //                     'description' => $batch->description,
    //                     'start_date' => $batch->start_date,
    //                     'end_date' => $batch->end_date,
    //                     'status' => $batch->status,
    //                     'completed' => $batch->completed,
    //                     'course_batches' => $courseBatches,
    //                     'programmes' => $courseBatches
    //                         ->pluck('course.programme')
    //                         ->filter()
    //                         ->unique('id')
    //                         ->values(),
    //                 ];
    //             });

    //         return response()->json([
    //             'success' => true,
    //             'data' => $batches
    //         ]);
    //     }




        public function programmeWithBatch(Request $request)
        {
            $today = Carbon::today();

            $batchQuery = Batch::where('completed', false)
                ->where('status', true);

            if ($request->has('filter')) {
                $filter = $request->filter;

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

            $programmes = $courses->map(function ($course) {
                $programme = $course->programme;
                if ($programme) {
                    $programmeData = $programme->toArray();
                    $programmeData['course_id'] = $course->id;
                    $programmeData['centre_id'] = $course->centre_id;
                    $programmeData['duration'] = $course->duration;
                    $programmeData['start_date'] = $course->start_date;
                    $programmeData['end_date'] = $course->end_date;
                    $programmeData['status'] = $course->status ?? true;
                    return $programmeData;
                }
                return null;
            })->filter()->unique(function ($item) {
                return $item['centre_id'] . '-' . $item['id'];
            })->values();

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
        $courseCategory = CourseCategory::all();

        return response()->json([
            'success' => true,
            'data' => $courseCategory
        ]);
    }




    public function getBranch()
    {
        $branch = Branch::where('status', 1)->get();

        return response()->json([
            'success' => true,
            'data' => $branch
        ]);
    }




    public function getBranchSummary()
    {
        $branches = Branch::withCount('centre')
            ->with(['centre'])
            ->get()
            ->map(function ($branch) {

                $centreIds = $branch->centre->pluck('id');
                $courses = Course::whereIn('centre_id', $centreIds)->get();
                $ProgrammeIds = $courses->pluck('programme_id');
                $programmes = Programme::whereIn('id', $ProgrammeIds)->get();
                $courseIds = $courses->pluck('id');
                $admittedUsersCount = UserAdmission::whereIn('course_id', $courseIds)
                    ->whereNotNull('confirmed')
                    ->count();

                return [
                    'branch_id' => $branch->id,
                    'branch_title' => $branch->title,
                    'total_centres' => $branch->centre_count,
                    'total_trained_coders' => $admittedUsersCount,
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
                unset($centre->branch);
                return $centre;
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
            'programmes' => $programmes,
        ]);
    }


    public function centresByBranch(Branch $branch)
    {
        $centres = $branch->centre()->get();

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

        $districts = District::query()
            ->where('branch_id', $branch->id)
            ->orderBy('title')
            ->get();

        return response()->json([
            'success' => true,
            'branch_id' => $branch->id,
            'branch' => $branch->title,
            'districts' => $districts,
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
            'centres' => $district->centres->values(),
        ]);
    }


}
