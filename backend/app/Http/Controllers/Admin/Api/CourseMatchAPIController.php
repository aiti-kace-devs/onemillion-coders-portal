<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseMatch;
use App\Models\CourseCategory;
use App\Models\Branch;
use App\Models\UserAdmission;
use App\Models\Course;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CourseMatchAPIController extends Controller
{



    public function index(Request $request)
    {
        $courseMatch = CourseMatch::with(['courseMatchOptions'])->get();

        return response()->json([
            'success' => true,
            'data' => $courseMatch
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





}
