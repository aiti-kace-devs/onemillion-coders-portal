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
            'experience' => 'required|string',
            'timeCommitment' => 'required|string',
            'careerGoal' => 'required|string',
            'interest' => 'required|string',
            'priority' => 'required|string',
        ]);

        // Fetch all courses and their categories (adjust as per your DB structure)
        $allCourses = Programme::with('category')->get();

        $scored = $allCourses->map(function ($course) use ($data) {
            $score = 0;

            // Experience level matching
            if ($data['experience'] === 'complete-beginner' && $course->difficulty_level === 'Beginner') $score += 3;
            if ($data['experience'] === 'some-experience' && in_array($course->difficulty_level, ['Beginner', 'Intermediate'])) $score += 2;
            if ($data['experience'] === 'intermediate' && in_array($course->difficulty_level, ['Intermediate', 'Advanced'])) $score += 2;
            if ($data['experience'] === 'advanced' && in_array($course->difficulty_level, ['Advanced', 'Expert'])) $score += 3;

            // Time commitment matching (assume $course->training_duration is in hours)
            $duration = intval($course->training_duration);
            if ($data['timeCommitment'] === 'part-time' && $duration <= 100) $score += 2;
            if ($data['timeCommitment'] === 'moderate' && $duration <= 200) $score += 2;
            if ($data['timeCommitment'] === 'intensive' && $duration > 200) $score += 2;

            // Interest matching (adjust category names as needed)
            if ($data['interest'] === 'data-e-analytics' && $course->category->name === 'Artificial Intelligence Training') $score += 3;
            if ($data['interest'] === 'cybersecurity' && $course->category->name === 'Cybersecurity') $score += 3;
            if ($data['interest'] === 'software-development' && in_array($course->category->name, ['Web Application Programming', 'Mobile Application Development'])) $score += 3;
            if ($data['interest'] === 'it-support' && in_array($course->category->name, ['Systems Administration', 'BPO Training'])) $score += 3;
            if ($data['interest'] === 'data-protection' && $course->category->name === 'DATA Protection') $score += 3;

            // Career goal matching
            if ($data['careerGoal'] === 'start-new-tech-career' && in_array($course->difficulty_level, ['Beginner', 'Intermediate'])) $score += 2;
            if ($data['careerGoal'] === 'enhance-current-role' && $course->category->name === 'DATA Protection') $score += 2;
            if ($data['careerGoal'] === 'get-promoted' && in_array($course->difficulty_level, ['Intermediate', 'Advanced'])) $score += 2;

            // Priority matching
            if ($data['priority'] === 'quick-job-entry' && in_array($course->category->name, ['Systems Administration', 'BPO Training'])) $score += 2;
            if ($data['priority'] === 'high-salary-potential' && in_array($course->category->name, ['Cybersecurity', 'Web Application Programming'])) $score += 2;
            if ($data['priority'] === 'work-life-balance' && in_array($course->category->name, ['DATA Protection', 'Artificial Intelligence Training'])) $score += 2;

            $course->score = $score;
            return $course;
        });

        // Filter, sort, and return top 3
        $top = $scored->filter(fn($c) => $c->training_program && $c->score > 0)
                    ->sortByDesc('score')
                    ->take(3)
                    ->values();

        return response()->json([
            'success' => true,
            'recommendations' => $top,
        ]);
    }




}
