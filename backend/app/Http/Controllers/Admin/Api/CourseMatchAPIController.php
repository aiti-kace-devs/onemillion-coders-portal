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



    public function fullRecommendation(Request $request)
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
            'ttile' => 'Your Course Matches',
            'description' => 'Based on your preferences, here are the courses that align best with your goals',
            'matches' => $result,
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
    
        // Get Programmes with ONLY needed columns + tags relationship
        $programmes = Programme::select('id', 'title', 'sub_title', 'duration', 'level', 'job_responsible', 'image', 'prerequisites')
            ->with('tags')
            ->get();
    
        // Score each programme by matching option IDs
        $scored = $programmes->map(function ($programme) use ($optionIds, $totalOptions) {
            $programmeOptionIds = $programme->tags->pluck('id')->toArray();
            $matches = count(array_intersect($optionIds, $programmeOptionIds));
            $percentage = $totalOptions > 0 ? round(($matches / $totalOptions) * 100) : 0;
    
            $programme->match_percentage = $percentage;
            $programme->match_count = $matches;
            return $programme;
        });
    
        // Filter and sort top 5 matches
        $top = $scored->filter(fn($p) => $p->match_count > 0)
                      ->sortByDesc('match_percentage')
                      ->take(5)
                      ->values();
    
        // Format response with ranking number
        $result = $top->map(function ($programme, $index) {
            return [
                'rank' => '#' . ($index + 1),
                'id' => $programme->id,
                'title' => $programme->title,
                'sub_title' => $programme->sub_title,
                'duration' => $programme->duration,
                'level' => $programme->level,
                'image' => $programme->image,
                'job_responsible' => $programme->job_responsible,
                'image' => $programme->image,
                'prerequisites' => $programme->prerequisites,
                'match_percentage' => $programme->match_percentage . '% Match',
            ];
        });
    
        return response()->json([
            'success' => true,
            'title' => 'Your Course Matches',
            'description' => 'Based on your preferences, here are the courses that align best with your goals',
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
