<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Programme;
use App\Models\CourseCategory;
use App\Models\Branch;
use App\Models\UserAdmission;
use App\Models\Centre;
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

            // Base query for ongoing batches with course batches
            $query = Batch::where('completed', false)
                ->where('status', true);

            // Apply filters based on batch dates
            if ($request->has('filter')) {
                $filter = $request->filter;

                if ($filter === 'ongoing') {
                    $query->where('start_date', '<=', $today)
                        ->where('end_date', '>=', $today);
                }

                if ($filter === 'upcoming') {
                    $query->where('start_date', '>', $today);
                }

                if ($filter === 'passed') {
                    $query->where('end_date', '<', $today)
                        ->orWhere('completed', true);
                }
            }

            // Get batches and flatten to unique programmes with their course batch info
            $programmes = $query->with([
                    'courseBatches.course.programme' => function ($q) {
                        $q->with(['category', 'courseCertification', 'courseModules'])
                            ->withCount('courseModules');
                    }
                ])
                ->whereHas('courseBatches')
                ->get()
                ->flatMap(function ($batch) {
                    return $batch->courseBatches->map(function ($cb) {
                        $programme = $cb->course->programme ?? null;

                        if (!$programme) {
                            return null;
                        }

                        return [
                            'id' => $programme->id,
                            'title' => $programme->title,
                            'description' => $programme->description,
                            'course_category_id' => $programme->course_category_id,
                            'cover_image_id' => $programme->cover_image_id,
                            'sub_title' => $programme->sub_title,
                            'level' => $programme->level,
                            'job_responsible' => $programme->job_responsible,
                            'image' => $programme->image,
                            'overview' => $programme->overview,
                            'prerequisites' => $programme->prerequisites,
                            'course_modules_count' => $programme->course_modules_count,
                            'duration' => $cb->duration,
                            'start_date' => $cb->start_date,
                            'end_date' => $cb->end_date,
                            'status' => $cb->status ?? true,
                            'created_at' => $cb->created_at,
                            'updated_at' => $cb->updated_at,
                            'category' => $programme->category,
                            'course_certification' => $programme->courseCertification,
                            'course_modules' => $programme->courseModules,
                        ];
                    });
                })
                ->filter()
                ->unique('id')
                ->values();

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
        $branch = Branch::all();

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
        $programmes = Programme::whereHas('centre', function ($query) use ($centre) {
            $query->where('centres.id', $centre->id);
        })->with(['category', 'coverImage'])->get();

        return response()->json([
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


}
