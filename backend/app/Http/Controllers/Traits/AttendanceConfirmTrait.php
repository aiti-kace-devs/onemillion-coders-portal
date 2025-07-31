<?php

namespace App\Http\Controllers\Traits;

use App\Models\UserAdmission;
use App\Models\Attendance;
use Carbon\Carbon;

trait AttendanceConfirmTrait
{
    public function confirmAttendanceLogic($data, $user)
    {
        $carbonDate = Carbon::parse($data['date']);
        if (!$carbonDate->is(Carbon::today()) && !$user->can('attendance.update')) {
            return ['success' => false, 'message' => 'You can only confirm attendance for today - ' . Carbon::today()->format('l jS F, Y')];
        }
        $userAdmitted = UserAdmission::where('user_id', $data['user_id'])
            ->whereNotNull('confirmed')
            ->first();
        if (!$userAdmitted) {
            return ['success' => false, 'message' => 'User not admitted. Cannot confirm attendance'];
        }
        $alreadyConfirmed = Attendance::where('user_id', $data['user_id'])
            ->where('date', $data['date'])
            ->first();
        if ($alreadyConfirmed) {
            return ['success' => true, 'message' => 'Attendance already confirmed.'];
        }
        $attendance = new Attendance();
        $attendance->user_id = $data['user_id'];
        $attendance->course_id = $userAdmitted->course_id;
        $attendance->date = $data['date'];
        $attendance->save();
        // UpdateAttendanceOnSheetJob::dispatch($attendance);
        return ['success' => true, 'message' => 'Attendance recorded successfully.'];
    }
}
