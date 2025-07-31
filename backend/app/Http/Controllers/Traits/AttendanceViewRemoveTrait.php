<?php

namespace App\Http\Controllers\Traits;

use App\Models\Attendance;
use App\Models\Course;
use Illuminate\Support\Facades\Auth;

trait AttendanceViewRemoveTrait
{
    public function viewAttendanceLogic($userId)
    {
        return Attendance::select('attendances.*', 'courses.created_at as course_created', 'courses.course_name')
            ->where('user_id', $userId)
            ->join('courses', 'courses.id', 'attendances.course_id')
            ->orderBy('date', 'desc')
            ->get();
    }

    public function removeAttendanceLogic($id)
    {
        $attendance = Attendance::find($id);
        if (!$attendance) {
            return ['success' => false, 'message' => 'Attendance not found'];
        }
        $attendance->delete();
        return ['success' => true, 'message' => 'Attendance removed successfully.'];
    }
}
