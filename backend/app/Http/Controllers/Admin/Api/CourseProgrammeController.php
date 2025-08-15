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




    public function programmeWithBatch(Request $request)
    {
        $query = Batch::with([
                'assignedCourseBatches.programme' => function ($q) {
                    $q->with(['category', 'courseCertification', 'courseModules'])
                    ->withCount('courseModules');
                }
            ])
            ->whereHas('assignedCourseBatches.programme');

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

        $batches = $query->get()
            ->map(function ($batch) {
                return [
                    'id'          => $batch->id,
                    'title'       => $batch->title,
                    'description' => $batch->description,
                    'start_date'  => $batch->start_date,
                    'end_date'    => $batch->end_date,
                    'programmes'  => $batch->assignedCourseBatches
                                        ->pluck('programme')
                                        ->unique('id')
                                        ->values(),
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $batches
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
