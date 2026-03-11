<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Centre;
use App\Models\CourseMatch;
use App\Models\CourseCategory;
use App\Models\Branch;
use App\Models\UserAdmission;
use App\Models\Programme;
use App\Models\Course;
use App\Models\Batch;
use App\Models\CourseBatch;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
            $today = Carbon::today()->toDateString();

            
            // Get Programme IDs that have ongoing courses with batch_id
            // Using the direct batch_id relationship on Course model
            $ongoingCourses = Course::join('admission_batches', 'courses.batch_id', '=', 'admission_batches.id')
                ->select(
                    'courses.*',
                    'admission_batches.title as batch_title',
                    'admission_batches.start_date as ab_start_date',
                    'admission_batches.end_date as ab_end_date',
                    'admission_batches.status',
                    'admission_batches.completed'
                )
                ->where('admission_batches.start_date', '<=', $today)
                ->where('admission_batches.end_date', '>=', $today)
                ->where('admission_batches.completed', false)
                ->where('admission_batches.status', true)
                ->get();
            
            // Get the actual programme IDs (through courses.programme_id)
            $ongoingProgrammeIds = $ongoingCourses->pluck('programme_id')->unique()->toArray();
            
            // Get unique centre IDs from ongoing courses
            $centreIds = $ongoingCourses->pluck('centre_id')->unique()->toArray();
            
            // Get Programmes with ONLY needed columns + tags relationship
            // Only include programmes that have ongoing course batches
            $programmes = Programme::select('id', 'title', 'sub_title', 'duration', 'level', 'job_responsible', 'image', 'prerequisites')
                ->with('tags')
                ->whereIn('id', $ongoingProgrammeIds)
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
            
            // Get centre IDs for top programmes (through their course batches)
            $topProgrammeIds = $top->pluck('id')->toArray();
            
            // Get courses for top programmes to get their centre IDs
            $topCourses = $ongoingCourses->whereIn('programme_id', $topProgrammeIds);
            
            // Get unique centre IDs for top programmes
            $topCentreIds = $topCourses->pluck('centre_id')->unique()->toArray();
            
            // Pre-fetch all centres with their branch info
            $centresMap = Centre::whereIn('id', $topCentreIds)
                ->with('branch:id,title')
                ->get()
                ->keyBy('id');
            
            // Format response with ranking number and each programme's own centre
            $result = $top->map(function ($programme, $index) use ($topCourses, $centresMap) {
                // Get the centre ID for this programme from its courses
                $programmeCourses = $topCourses->where('programme_id', $programme->id);
                $centreId = $programmeCourses->first()?->centre_id;
                
                $centre = $centreId && isset($centresMap[$centreId]) ? $centresMap[$centreId] : null;
                
                return [
                    'rank' => '#' . ($index + 1),
                    'id' => $programme->id,
                    'title' => $programme->title,
                    'sub_title' => $programme->sub_title,
                    'duration' => $programme->duration,
                    'level' => $programme->level,
                    'image' => $programme->image,
                    'job_responsible' => $programme->job_responsible,
                    'prerequisites' => $programme->prerequisites,
                    'match_percentage' => $programme->match_percentage . '% Match',
                    'centre' => $centre ? [
                        'id' => $centre->id,
                        'title' => $centre->title,
                        'gps_address' => $centre->gps_address,
                        'is_pwd_friendly' => $centre->is_pwd_friendly,
                        'wheelchair_accessible' => $centre->wheelchair_accessible,
                        'has_access_ramp' => $centre->has_access_ramp,
                        'has_accessible_toilet' => $centre->has_accessible_toilet,
                        'has_elevator' => $centre->has_elevator,
                        'region' => $centre->branch ? [
                            'title' => $centre->branch->title,
                        ] : null,
                    ] : null,
                ];
            });
            

            return response()->json([
                'success' => true,
                'title' => 'Your Course Matches',
                'description' => 'Based on your preferences, here are the courses that align best with your goals',
                'matches' => $result,
            ]);
        }
        





    // public function recommend(Request $request)
    // {
    //     $data = $request->validate([
    //         'option_ids' => 'required|array',
    //         'option_ids.*' => 'integer|exists:course_match_options,id',
    //     ]);
        
    //     $optionIds = $data['option_ids'];
    //     $totalOptions = count($optionIds);
        
    //     // Get Programmes with ONLY needed columns + tags relationship
    //     $programmes = Programme::select('id', 'title', 'sub_title', 'duration', 'level', 'job_responsible', 'image', 'prerequisites')
    //         ->with('tags')
    //         ->get();
        
    //     // Score each programme by matching option IDs
    //     $scored = $programmes->map(function ($programme) use ($optionIds, $totalOptions) {
    //         $programmeOptionIds = $programme->tags->pluck('id')->toArray();
    //         $matches = count(array_intersect($optionIds, $programmeOptionIds));
    //         $percentage = $totalOptions > 0 ? round(($matches / $totalOptions) * 100) : 0;
        
    //         $programme->match_percentage = $percentage;
    //         $programme->match_count = $matches;
    //         return $programme;
    //     });
        
    //     // Filter and sort top 5 matches
    //     $top = $scored->filter(fn($p) => $p->match_count > 0)
    //                   ->sortByDesc('match_percentage')
    //                   ->take(5)
    //                   ->values();
        
    //     // Format response with ranking number
    //     $result = $top->map(function ($programme, $index) {
    //         return [
    //             'rank' => '#' . ($index + 1),
    //             'id' => $programme->id,
    //             'title' => $programme->title,
    //             'sub_title' => $programme->sub_title,
    //             'duration' => $programme->duration,
    //             'level' => $programme->level,
    //             'image' => $programme->image,
    //             'job_responsible' => $programme->job_responsible,
    //             'image' => $programme->image,
    //             'prerequisites' => $programme->prerequisites,
    //             'match_percentage' => $programme->match_percentage . '% Match',
    //         ];
    //     });
        

    //     return response()->json([
    //         'success' => true,
    //         'title' => 'Your Course Matches',
    //         'description' => 'Based on your preferences, here are the courses that align best with your goals',
    //         'matches' => $result,
    //     ]);
    // }
    




    public function allProgrammesWithCourseMatch()
    {
        $programmes = Programme::with(['tags.courseMatch'])->get();

        return response()->json([
            'success' => true,
            'data' => $programmes
        ]);
    }

}
