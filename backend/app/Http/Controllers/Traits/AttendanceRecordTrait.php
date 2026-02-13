<?php

namespace App\Http\Controllers\Traits;

use App\Models\UserAdmission;
use App\Models\Course;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use ReallySimpleJWT\Token;

trait AttendanceRecordTrait
{
    public function recordAttendanceLogic($scannedToken)
    {
        $secret = config('app.jwt_token');
        if (!Token::validate($scannedToken, $secret) || !Token::validateExpiration($scannedToken)) {
            return ['status' => false, 'message' => 'Link expired'];
        }
        $decodedData = Token::getPayload($scannedToken);
        $decodedUserIdData = json_decode($decodedData['user_id'], true);
        if (!is_array($decodedUserIdData) || !isset($decodedUserIdData['course_id']) || !isset($decodedUserIdData['date'])) {
            return ['status' => false, 'message' => 'Unable to confirm attendance'];
        }
        $date = Carbon::parse($decodedUserIdData['date']);
        if ($date->isWeekend()) {
            return ['status' => false, 'message' => 'Attendance cannot be taken for weekends'];
        }
        $date = $date->format('Y-m-d');
        $user_id = Auth::user()->userId;
        $confirmedAdmission = UserAdmission::where('user_id', $user_id)->whereNotNull('confirmed')->first();
        if (!$confirmedAdmission) {
            return ['status' => false, 'message' => 'User not admitted. Cannot confirm attendance'];
        }
        $course = Course::find($decodedUserIdData['course_id']);
        $admittedCourse = Course::find($confirmedAdmission['course_id']);
        if (isset($decodedUserIdData['online']) && $decodedUserIdData['online'] === 'onlineForAll') {
            if ($this->createAttendanceLogic($user_id, $admittedCourse->id, $date)) {
                return ['status' => true, 'message' => 'Attendance confirmed successfully.'];
            } else {
                return ['status' => true, 'message' => 'Attendance already confirmed.'];
            }
        }
        if ($course->course_name != $admittedCourse->course_name) {
            return ['status' => false, 'message' => 'User not admitted unto this course'];
        }
        if ($course->location != $admittedCourse->location && $decodedUserIdData['online'] == 'false') {
            return ['status' => false, 'message' => 'User not admitted unto this course location'];
        }
        $attendanceRecord = Attendance::where('user_id', $user_id)->whereDate('date', $date)->first();
        if ($attendanceRecord) {
            return ['status' => true, 'message' => 'Attendance already confirmed.'];
        }
        if ($this->createAttendanceLogic($user_id, $admittedCourse->id, $date)) {
            return ['status' => true, 'message' => 'Attendance confirmed successfully.'];
        }
        return ['status' => false, 'message' => 'Unable to confirm attendance'];
    }

    public function createAttendanceLogic($user_id, $course_id, $date)
    {
        $attendanceRecord = Attendance::where('user_id', $user_id)->whereDate('date', $date)->first();
        if ($attendanceRecord) {
            return false;
        }
        $attendance = new Attendance();
        $attendance->user_id = $user_id;
        $attendance->course_id = $course_id;
        $attendance->date = $date;
        $attendance->save();
        // UpdateAttendanceOnSheetJob::dispatch($attendance);
        return true;
    }
}
