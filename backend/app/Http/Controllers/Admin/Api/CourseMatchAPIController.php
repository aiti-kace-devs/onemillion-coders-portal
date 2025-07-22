<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseMatch;
use App\Models\CourseCategory;
use App\Models\Branch;
use App\Models\UserAdmission;
use App\Models\Programme;
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



    public function recommend(Request $request)
    {
        $data = $request->validate([
            'option_ids' => 'required|array',
            'option_ids.*' => 'integer|exists:course_match_options,id',
        ]);

        $optionIds = $data['option_ids'];
        $totalOptions = count($optionIds);

        // Get all Programmes with their tags (CourseMatchOptions)
        $programmes = Programme::with('tags')->get();

        // Score each programme by how many of the selected options it has
        $scored = $programmes->map(function ($programme) use ($optionIds, $totalOptions) {
            $programmeOptionIds = $programme->tags->pluck('id')->toArray();
            $matches = count(array_intersect($optionIds, $programmeOptionIds));
            $percentage = $totalOptions > 0 ? round(($matches / $totalOptions) * 100) : 0;
            $programme->match_percentage = $percentage;
            $programme->match_count = $matches;
            return $programme;
        });

        // Get top 5 matches with at least 1 match, sorted by match count and percentage
        $top = $scored->filter(fn($p) => $p->match_count > 0)
                      ->sortByDesc('match_percentage')
                      ->take(5)
                      ->values();

        // Return all Programme fields + match_percentage
        $result = $top->map(function ($programme) {
            $arr = $programme->toArray();
            $arr['match_percentage'] = $programme->match_percentage;
            return $arr;
        });

        return response()->json([
            'success' => true,
            'matches' => $result,
        ]);
    }





    public function allProgrammesWithCourseMatch()
    {
        $programmes = Programme::with(['tags.courseMatch'])->get();

        return response()->json([
            'success' => true,
            'data' => $programmes
        ]);
    }

}
