<?php

namespace App\Http\Controllers;

use App\Helpers\GoogleSheets;
use App\Models\Course;
use App\Models\UserAdmission;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use ReallySimpleJWT\Token;
use App\Jobs\UpdateAttendanceOnSheetJob;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function generateQRCodeData(Request $request)
    {
        $data = $request->validate([
            'course_id' => 'exists:courses,id',
            'date' => 'date|before_or_equal:' . now()->toDateString(),
            'online' => 'sometimes',
            'validity' => 'sometimes',
        ]);

        $date = Carbon::parse($data['date']);

        // if ($date->isWeekend()) {
        //     return response()->json(["message" => 'Date is a weekend'], 400);
        // }

        // $course = Course::findOrFail($courseId);
        $course = Course::findOrFail($data['course_id']);

        // $scret should be 12 char and must contain upper,lower,number and specialchar
        $secret = env('JWT_KEY');

        $dataToEncode = json_encode([
            'course_id' => $course->id,
            'location' => $course->location,
            'date' => $data['date'],
            'online' => $data['online'] ?? false,
        ]);

        $toAdd = $data['validity'] ?? 30;

        $expiration = Carbon::now()->addMinutes($toAdd)->timestamp;

        $issuer = 'attendance_app';
        // dd($secret);
        $token = Token::create($dataToEncode, $secret, $expiration, $issuer);
        $url = url('/student/mark_attendance?scanned_data=' . $token);
        return response()->json(['data' => $token, 'url' => $url]);
        // $loginUrl = url('/login?scanned_data=' . urlencode($token));
        // $qrCode = QrCode::size(300)->generate($loginUrl);

        // return view('attendance.qrcode', compact('qrCode', 'token'));
    }

    public function recordAttendance(Request $request)
    {
        $scannedToken = $request->input('scanned_data');

        $secret = env('JWT_KEY');

        try {
            if (Token::validate($scannedToken, $secret) && Token::validateExpiration($scannedToken)) {
                $decodedData = Token::getPayload($scannedToken);
                $decodedUserIdData = json_decode($decodedData['user_id'], true);
                if (!is_array($decodedUserIdData) || !isset($decodedUserIdData['course_id']) || !isset($decodedUserIdData['date'])) {
                    redirect('/student/attendance')->with([
                        'flash' => 'Unable to confirm attendance',
                        'key' => 'error',
                    ]);
                }

                $date = Carbon::parse($decodedUserIdData['date']);

                if ($date->isWeekend()) {
                    return redirect(url('/student/attendance'))->with([
                        'flash' => 'Attendance cannot be taken for weekends',
                        'key' => 'error',
                    ]);
                }

                $date = $date->format('Y-m-d');

                $user_id = Auth::user()->userId;

                $confirmedAdmission = UserAdmission::where('user_id', $user_id)->whereNotNull('confirmed')->first();

                if (!$confirmedAdmission) {
                    return redirect(url('/student/attendance'))->with([
                        'flash' => 'User not admitted. Cannot confirm attendance',
                        'key' => 'error',
                    ]);
                }

                $course = Course::find($decodedUserIdData['course_id']);
                $admittedCourse = Course::find($confirmedAdmission['course_id']);

                // if online for all, ignore both course id and location
                if (isset($decodedUserIdData['online']) && $decodedUserIdData['online'] === 'onlineForAll') {
                    if ($this->createAttendance($user_id, $admittedCourse->id, $date)) {
                        return redirect(url('/student/attendance'))->with([
                            'flash' => 'Attendance confirmed successfully.',
                            'key' => 'success',
                        ]);
                    } else {
                        return redirect(url('/student/attendance'))->with([
                            'flash' => 'Attendance already confirmed.',
                            'key' => 'success',
                        ]);
                    }
                }

                // if online, ignore location
                if ($course->course_name != $admittedCourse->course_name) {
                    return redirect(url('/student/attendance'))->with([
                        'flash' => 'User not admitted unto this course',
                        'key' => 'error',
                    ]);
                }

                if ($course->location != $admittedCourse->location && $decodedUserIdData['online'] == 'false') {
                    return redirect(url('/student/attendance'))->with([
                        'flash' => 'User not admitted unto this course location',
                        'key' => 'error',
                    ]);
                }

                $attendanceRecord = Attendance::where('user_id', $user_id)->whereDate('date', $date)->first();

                if ($attendanceRecord) {
                    return redirect(url('/student/attendance'))->with([
                        'flash' => 'Attendance already confirmed.',
                        'key' => 'success',
                    ]);
                }

                if ($this->createAttendance($user_id, $admittedCourse->id, $date)) {
                    return redirect(url('/student/attendance'))->with([
                        'flash' => 'Attendance confirmed successfully.',
                        'key' => 'success',
                    ]);
                }
            } else {
                return redirect('/student/attendance')->with([
                    'flash' => 'Link expired',
                    'key' => 'error',
                ]);
            }
        } catch (\Exception $e) {
            return redirect('/student/attendance')->with([
                'flash' => 'Unable to confirm attendance',
                'key' => 'error',
            ]);
        }
    }

    private function createAttendance($user_id, $course_id, $date)
    {
        // if (Carbon::parse($date)->isWeekend()) {
        //     return false;
        // }

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
    public function confirmAttendance(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,userId',
            'course_id' => 'required|exists:courses,id',
            'date' => 'required|date|before_or_equal:' . now()->toDateString(),
        ]);
        $carbonDate = Carbon::parse($data['date']);
        // if ($carbonDate->isWeekend()) {
        //     return response()->json(['message' => 'Cannot confirm attendance for a weekend', 'success' => false]);
        // }

        if (!$carbonDate->is(Carbon::today()) && !auth()->user()->can('attendance.update')) {
            return response()->json(['message' => 'You can only confirm attendance for today - ' . Carbon::today()->format('l jS F, Y'), 'success' => false]);
        }


        $userAdmitted = UserAdmission::where('user_id', $data['user_id'])
            ->whereNotNull('confirmed')
            ->first();
        if (!$userAdmitted) {
            return response()->json(['message' => 'User not admitted. Cannot confirm attendance', 'success' => false]);
        }

        $alreadyConfirmed = Attendance::where('user_id', $data['user_id'])
            ->where('date', $data['date'])
            ->first();
        if ($alreadyConfirmed) {
            return response()->json(['message' => 'Attendance already confirmed.', 'success' => true]);
        }

        $attendance = new Attendance();
        $attendance->user_id = $data['user_id'];
        $attendance->course_id = $userAdmitted->course_id;
        $attendance->date = $data['date'];
        $attendance->save();

        // UpdateAttendanceOnSheetJob::dispatch($attendance);

        return response()->json(['message' => 'Attendance recorded successfully.', 'success' => true]);
    }

    public function viewAttendance()
    {
        $userId = Auth::user()->userId;
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

    public function removeAttendance($id)
    {

        $attendance = Attendance::find($id);

        if (!$attendance) {
            return redirect()->back()->with([
                'flash' => 'Attendance not found',
                'key' => 'error'
            ]);
        }
        $attendance->delete();
        return redirect()->back()->with([
            'flash' => 'Attendance removed successfully.',
            'key' => 'success'
        ]);
        // return response()->json(['message' => 'Attendance removed successfully.', 'success' => true]);
    }
}
