<?php

namespace App\Http\Controllers\Traits;

use App\Models\Course;
use Carbon\Carbon;
use ReallySimpleJWT\Token;

trait AttendanceQRCodeTrait
{
    public function generateQRCodeDataLogic($data)
    {
        $date = Carbon::parse($data['date']);
        $course = Course::findOrFail($data['course_id']);
        $secret = env('JWT_KEY');
        $dataToEncode = json_encode([
            'course_id' => $course->id,
            'location' => $course->location,
            'date' => $data['date'],
            'online' => $data['online'] ?? false,
        ]);
        $toAdd = isset($data['validity']) ? (int)$data['validity'] : 30;
        $expiration = Carbon::now()->addMinutes($toAdd)->timestamp;
        $issuer = 'attendance_app';
        $token = Token::create($dataToEncode, $secret, $expiration, $issuer);
        $url = url('/student/mark_attendance?scanned_data=' . $token);
        return ['data' => $token, 'url' => $url];
    }
}
