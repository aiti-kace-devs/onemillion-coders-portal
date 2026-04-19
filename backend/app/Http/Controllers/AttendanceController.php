<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\UserAdmission;
use Carbon\Carbon;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function viewAttendance()
    {
        $user = Auth::guard('web')->user();
        
        if ($user->course?->isOnlineProgramme()) {
            return redirect()->route('student.dashboard');
        }

        $userId = $user->userId;
        $attendances = Attendance::select('attendances.*', 'courses.created_at as course_created', 'courses.course_name')
            ->where('user_id', $userId)
            ->join('courses', 'courses.id', 'attendances.course_id')
            ->orderBy('date', 'desc')
            ->get();

        // Get the user's admitted course
        $userAdmitted = UserAdmission::where('user_id', $userId)
            ->whereNotNull('confirmed')
            ->with('course')
            ->first();

        $totalSessions = 0;

        if ($userAdmitted && $userAdmitted->course && $userAdmitted->course->start_date && $userAdmitted->course->end_date) {
            $start = Carbon::parse($userAdmitted->course->start_date);
            $end = Carbon::parse($userAdmitted->course->end_date);
            // Count weekdays (Mon-Fri) between start and end date, inclusive
            $totalSessions = $start->diffInWeekdays($end) + (!$start->isWeekend() ? 1 : 0);
        }

        return Inertia::render('Student/Attendance', compact('attendances', 'totalSessions'));

        // return view('student.attendance', compact('attendance'));
    }
}
