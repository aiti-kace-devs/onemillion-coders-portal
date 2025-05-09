<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CourseCompleted;
use Carbon\Carbon;

class CourseCompletedController extends Controller
{
    /**
     * Display a listing of the course completions.
     */
    public function index(Request $request)
    {
           // First, sync data from attendance to course_completed
           $this->syncCompletionsFromAttendance();

        // Fetch all course completions with user and course information
        $courseCompletions = DB::table('course_completed')
            ->join('users', 'course_completed.user_id', '=', 'users.userId')
            ->join('courses', 'course_completed.course_id', '=', 'courses.id')
            ->select('course_completed.*', 'users.name as user_name', 'courses.course_name as course_title')
            ->orderBy('course_completed.completed_at', 'desc')
            ->get();



        return view('admin.course_completed', compact('courseCompletions'));
    }

    /**
     * Delete a course completion record.
     */
    private function syncCompletionsFromAttendance()
    {
        // Find users who have attended courses more than 10 times
        $completions = DB::table('attendances')
            ->select(
                'user_id',
                'course_id',
                DB::raw('COUNT(date) AS date_count'),
                DB::raw('MAX(date) AS completed_at')
            )
            ->groupBy('user_id', 'course_id')
            ->having('date_count', '>', 10)
            ->orderBy('date_count', 'desc')
            ->get();

        // For each eligible completion, insert or update the course_completed record
        foreach($completions as $completion) {
            CourseCompleted::updateOrCreate(
                [
                    'user_id' => $completion->user_id,
                    'course_id' => $completion->course_id
                ],
                [
                    'completed_at' => $completion->completed_at
                ]
            );
        }
    }

    /**
     * Manually refresh course completion data from attendance records
     */
    public function refreshCompletions()
    {
        $this->syncCompletionsFromAttendance();

        return redirect()->route('admin.course_completed.index')
            ->with('success', 'Course completion data has been refreshed from attendance records');
    }

    /**
     * Delete a course completion record.
     */
    public function destroy($id)
    {
        try {
            $completion = CourseCompleted::findOrFail($id);
            $completion->delete();

            return redirect()->route('admin.course_completed.index')
                ->with('success', 'Course completion record deleted successfully');
        } catch (Exception $e) {
            Log::error('Error deleting course completion: ' . $e->getMessage());
            return redirect()->route('admin.course_completed.index')
                ->with('error', 'An error occurred while deleting the record');
        }
    }

    /**
     * Filter course completions by date range.
     */
    public function filter(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = DB::table('course_completed')
            ->join('users', 'course_completed.user_id', '=', 'users.userId')
            ->join('courses', 'course_completed.course_id', '=', 'courses.id')
            ->select('course_completed.*', 'users.name as user_name', 'courses.course_name as course_title');

        if ($startDate) {
            $query->whereDate('course_completed.completed_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('course_completed.completed_at', '<=', $endDate);
        }

        $courseCompletions = $query->orderBy('course_completed.completed_at', 'desc')->get();

        return view('admin.course_completed', compact('courseCompletions'));
    }

    /**
     * Export course completions to CSV.
     */
//     public function export()
//     {
//         $completions = DB::table('course_completed')
//             ->join('users', 'course_completed.user_id', '=', 'users.userId')
//             ->join('courses', 'course_completed.course_id', '=', 'courses.id')
//             ->select(
//                 'users.name',
//                 'users.email',
//                 'courses.title',
//                 'course_completed.completed_at'
//             )
//             ->orderBy('course_completed.completed_at', 'desc')
//             ->get();

//         $headers = [
//             'Content-Type' => 'text/csv',
//             'Content-Disposition' => 'attachment; filename="course_completions.csv"',
//         ];

//         $callback = function() use ($completions) {
//             $file = fopen('php://output', 'w');
//             fputcsv($file, ['User Name', 'Email', 'Course Title', 'Completion Date']);

//             foreach ($completions as $completion) {
//                 fputcsv($file, [
//                     $completion->name,
//                     $completion->email,
//                     $completion->title,
//                     Carbon::parse($completion->completed_at)->format('Y-m-d H:i:s')
//                 ]);
//             }

//             fclose($file);
//         };

//         return response()->stream($callback, 200, $headers);
//     }
}
