<?php

namespace App\Http\Controllers;

use App\Mail\StudentAdmitted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\Oex_student;
use App\Models\Oex_exam_master;
use App\Models\Oex_question_master;
use App\Models\Oex_result;
use App\Models\User;
use App\Models\CourseSession;
use App\Models\Course;
use App\Models\UserAdmission;
use App\Models\user_exam;
use Illuminate\Support\Carbon;
use App\Helpers\GoogleSheets;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Jobs\AdmitStudentJob;
use App\Jobs\TestSubmittedJob;
use App\Models\AdmissionRejection;

class StudentOperation extends Controller
{
    //student dashboard
    public function dashboard()
    {
        if (Auth::user()->isAdmitted()) {
            return redirect('/student/id-qrcode');
        }

        $data['portal_exams'] = user_exam::select(['user_exams.*', 'users.name', 'oex_exam_masters.*', 'oex_categories.name as category_name'])
            ->selectRaw('(SELECT count(id) from oex_question_masters where exam_id = oex_exam_masters.id) as question_count', [])
            ->join('users', 'users.id', '=', 'user_exams.user_id')
            ->join('oex_exam_masters', 'user_exams.exam_id', '=', 'oex_exam_masters.id')
            ->orderBy('user_exams.exam_id', 'desc')
            ->join('oex_categories', 'oex_exam_masters.category', '=', 'oex_categories.id')
            ->where('user_exams.user_id', Session::get('id'))
            ->where('user_exams.std_status', '1')
            ->get()
            ->toArray();

        // $data['portal_exams'] = Oex_exam_master::select(['oex_exam_masters.*', 'oex_categories.name as cat_name'])
        //     ->join('oex_categories', 'oex_exam_masters.category', '=', 'oex_categories.id')
        //     ->orderBy('id', 'desc')->where('oex_exam_masters.status', '1')->get()->toArray();
        return view('student.dashboard', $data);
    }
    public function profile()
    {
        // Get the current authenticated user
        $user = Auth::user();

        // Get course details if available in user's record
        $course = null;
        if (!empty($user->exam)) {
            // Assuming 'exam' field in users table holds the course_id
            $course = Course::find($user->registered_course);
        }

        return view('student.profile', compact('user', 'course'));
    }

    // application status
    public function application_status()
    {
        $user_exam = user_exam::where('user_id', Session::get('id'))->first();
        $user_admission = UserAdmission::where('user_id', Auth::user()->userId)->first();
        // dd($exam_submitted, $data);

        return view('student.application-status', compact('user_exam', 'user_admission'));
    }

    //Exam page
    public function exam()
    {
        if (Auth::user()->isAdmitted()) {
            return redirect('/student/id-qrcode');
        }
        $student_info = user_exam::select(['user_exams.*', 'users.name', 'oex_exam_masters.title', 'oex_exam_masters.exam_date', 'users.created_at as registered'])
            ->join('users', 'users.id', '=', 'user_exams.user_id')
            ->join('oex_exam_masters', 'user_exams.exam_id', '=', 'oex_exam_masters.id')
            ->orderBy('user_exams.exam_id', 'desc')
            ->where('user_exams.user_id', Session::get('id'))
            ->where('user_exams.std_status', '1')
            ->get()
            ->toArray();

        return view('student.exam', ['student_info' => $student_info]);
    }

    //join exam page
    public function join_exam($id)
    {
        if (Auth::user()->isAdmitted()) {
            return redirect('/student/id-qrcode');
        }

        $questionSets = Oex_question_master::select('exam_set_id')->distinct()->pluck('exam_set_id');
        $randomExamId = $questionSets->random();
        $question = Oex_question_master::where('exam_set_id', $randomExamId)->inRandomOrder()->get();

        // $question = Oex_question_master::where('exam_id', $id)->inRandomOrder()->get();
        $user_exam = user_exam::where('exam_id', $id)->where('user_id', Session::get('id'))->get()->first();

        if ($user_exam && $user_exam->submitted) {
            return redirect(url('student/exam'))->with([
                'flash' => 'Unable to take exam. Test already submitted',
                'key' => 'error',
            ]);
        }

        $exam = Oex_exam_master::where('id', $id)->get()->first();
        $now = Carbon::now();

        if ($now->isAfter(new Carbon($exam->exam_date))) {
            return redirect(url('student/exam'))->with([
                'flash' => 'Unable to take exam. Exam deadline was ' . $exam->exam_date,
                'key' => 'error',
            ]);
        }

        // 48 hours to finish exam
        $userCreatedAt = new Carbon(Auth::user()->created_at);
        $userCreatedAtPlusDeadlineDays = $userCreatedAt->addDays(config(EXAM_DEADLINE_AFTER_REGISTRATION, 2));

        if ($userCreatedAtPlusDeadlineDays->isBefore($now)) {
            return redirect(url('student/exam'))->with([
                'flash' => 'Unable to take exam. Time to take exams has elapsed',
                'key' => 'error',
            ]);
        }

        $usedTime = 0;
        if ($user_exam && $user_exam->started) {
            $start = new Carbon($user_exam->started);

            $usedTime = $now->diffInMinutes($start);
        }
        if ($usedTime > $exam->exam_duration) {
            // time elapsed update exam status
            $user_exam->submitted = now();
            $user_exam->update();

            return redirect(url('student/exam'))->with([
                'flash' => 'Unable to take exam. Exam duration time has elapsed. ' . $usedTime . ' mins has passed since user started exams.',
                'key' => 'error',
            ]);
        }
        // dd($question->pluck("id"));
        return view('student.join_exam', ['question' => $question, 'exam' => $exam, 'usedTime' => $usedTime]);
    }

    // start exam
    public function start_exam($id)
    {
        $user_exam = user_exam::where('exam_id', $id)->where('user_id', Session::get('id'))->get()->first();
        $arr = ['status' => 'true', 'message' => 'started successfully'];
        if (!$user_exam->started) {
            user_exam::updateOrCreate(
                [
                    'user_id' => Session::get('id'),
                    'exam_id' => $id,
                ],
                ['started' => Carbon::now()->toDateTimeString()],
            );
        }

        return json_encode($arr);
    }

    //On submit
    public function submit_questions(Request $request)
    {
        $std_info = user_exam::where('user_id', Session::get('id'))->where('exam_id', $request->exam_id)->get()->first();

        if ($std_info && $std_info->submitted) {
            $res = Oex_result::where('exam_id', $request->exam_id)->where('user_id', Session::get('id'))->get()->first();
            $yes_ans = $res->yes_ans;
            $total = $res->yes_ans + $res->no_ans;
            $percentage = round(($yes_ans / $total) * 100);

            return redirect(url('student/exam'))->with([
                // 'flash' => "Test already submitted on this exam. Submission Date: {$std_info->submitted} .Result: {$percentage}% ({$yes_ans}/{$total})",
                'flash' => "Test already submitted on this exam. Submission Date: {$std_info->submitted}",
                'key' => 'info',
            ]);
        }

        $yes_ans = 0;
        $no_ans = 0;
        $data = $request->all();
        $result = [];
        $exam_set_id = null;
        for ($i = 1; $i <= $request->index; $i++) {
            // set exam_set on first iteration

            if (isset($data['question' . $i])) {
                $q = Oex_question_master::where('id', $data['question' . $i])
                    ->get()
                    ->first();
                if ($i == 1) {
                    $exam_set_id = $q->exam_set_id;
                }

                if ($q->ans == $data['ans' . $i]) {
                    $result[$data['question' . $i]] = 'YES';
                    $yes_ans++;
                } else {
                    $result[$data['question' . $i]] = 'NO';
                    $no_ans++;
                }
            }
        }

        $std_info->exam_joined = 1;
        $std_info->submitted = Carbon::now()->toDateTimeString();
        $std_info->update();

        $user = User::where('id', Session::get('id'))->first();
        $userId = $user->userId;

        $res = new Oex_result();
        $res->exam_id = $request->exam_id;
        $res->user_id = Session::get('id');
        $res->yes_ans = $yes_ans;
        $res->no_ans = $no_ans;
        $res->result_json = json_encode($result);
        $total = $yes_ans + $no_ans;
        $res->exam_set = $exam_set_id;
        $percentage = round(($yes_ans / $total) * 100);
        $res->save();
        // $storedResult = Oex_result::where('user_id', $user->id)
        //     ->where('exam_id', $request->exam_id)
        //     ->first();
        // GoogleSheets::updateGoogleSheets($userId, ['result' => $storedResult->yes_ans]);
        TestSubmittedJob::dispatch($user, $res);

        return redirect(url('student/exam'))->with([
            // 'flash' => "Test submitted successfully. Result: {$percentage}%  {$yes_ans}/{$total}",
            'flash' => 'Test submitted successfully.',
            'key' => 'success',
        ]);
    }

    //Applying for exam
    public function apply_exam($id)
    {
        $checkuser = user_exam::where('user_id', Session::get('id'))->where('exam_id', $id)->get()->first();

        if ($checkuser) {
            $arr = ['status' => 'false', 'message' => 'Already applied, see your exam section'];
        } else {
            $exam_user = new user_exam();

            $exam_user->user_id = Session::get('id');
            $exam_user->exam_id = $id;
            $exam_user->std_status = 1;
            $exam_user->exam_joined = 0;

            $exam_user->save();

            $arr = ['status' => 'true', 'message' => 'applied successfully', 'reload' => url('student/dashboard')];
        }

        echo json_encode($arr);
    }

    //View Result
    public function view_result($id)
    {
        $data['result_info'] = Oex_result::where('exam_id', $id)->where('user_id', Session::get('id'))->get()->first();

        $data['student_info'] = User::where('id', Session::get('id'))->get()->first();

        $data['exam_info'] = Oex_exam_master::where('id', $id)->get()->first();

        return view('student.view_result', $data);
    }

    //View answer
    public function view_answer($id)
    {
        $data['question'] = Oex_question_master::where('exam_id', $id)->get()->toArray();

        return view('student.view_amswer', $data);
    }

    public function reset_exam($exam_id, $user_id)
    {
        $user = User::findOrFail($user_id);
        $user->created_at = Carbon::now()->toDateTimeString();
        $user->updated_at = Carbon::now()->toDateTimeString();
        $user->save();

        user_exam::updateOrCreate(
            [
                'user_id' => $user_id,
                'exam_id' => $exam_id,
            ],
            ['started' => null, 'submitted' => null, 'exam_joined' => 0, 'std_status' => 1],
        );

        Oex_result::where('user_id', $user_id)->where('exam_id', $exam_id)->delete();

        return redirect(url('admin/manage_students'))->with([
            'flash' => 'Exam reset successfully',
            'key' => 'success',
        ]);
    }

    public function select_session_view($user_id)
    {
        $admission = UserAdmission::where('user_id', $user_id)->firstOrFail();
        $user = User::select('id', 'name', 'userId')->where('userId', $user_id)->first();

        // if ($admission->confirmed) {
        //     return view('student.session-select.index', [
        //         'confirmed' => true,
        //         'user' => $user,
        //         'session' => CourseSession::where('id', $admission->session)->first(),
        //     ]);
        // }
        $courseDetails = Course::find($admission->course_id);
        $sessions = CourseSession::where('course_id', $courseDetails->id)->get();
        // $sessions->each(function($s){
        //     $used = UserAdmission::where('session', $s->id)->whereNotNull('confirmed')->count();

        // });
        return view('student.session-select.index', [
            'user' => $user,
            'sessions' => $sessions,
            'course' => $courseDetails,
            'confirmed' => false,
            'admission' => $admission,
            'session' => CourseSession::where('id', $admission->session)->first(),
        ]);
    }

    public function confirm_session(Request $request, $user_id)
    {
        try {
            $data = $request->validate([
                'session_id' => 'required|exists:course_sessions,id',
            ]);

            $admission = UserAdmission::where('user_id', $user_id)->firstOrFail();
            $changingSession = $admission->confirmed && $admission->session;

            if ($changingSession && !config(ALLOW_SESSION_CHANGE, false)) {
                return redirect(url('student/select-session/' . $user_id))->with([
                    'flash' => 'Unable to change session at this time. Contact administrator',
                    'key' => 'error',
                ]);
            }

            $courseDetails = Course::find($admission->course_id);
            $session = CourseSession::where('course_id', $courseDetails->id)->where('id', $data['session_id'])->first();

            if (!$session) {
                return redirect(url('student/select-session/' . $user_id))->with([
                    'flash' => 'Unable to confirm session. Try again later',
                    'key' => 'error',
                ]);
            }

            $slotLeft = $session->slotLeft();

            if ($slotLeft < 1) {
                return redirect(url('student/select-session/' . $user_id))->with([
                    'flash' => 'Unable to confirm session. No slots available',
                    'key' => 'error',
                ]);
            }

            $admission->confirmed = now();
            $admission->session = $session->id;
            // $admission->email_sent = now();
            $admission->location = $courseDetails->location;
            $admission->save();

            if (!$changingSession) {
                AdmitStudentJob::dispatch($admission);
            }
            return redirect(url('student/select-session/' . $user_id))->with([
                'flash' => $changingSession ? 'Session changed successfully' : 'Confirmation successful',
                'key' => 'success',
            ]);
        } catch (\Exception $e) {
            // Log::error($e);
            return redirect(url('student/select-session/' . $user_id))->with([
                'flash' => 'Unable to confirm session. No slots available. Refresh page and try again later',
                'key' => 'error',
            ]);
        }
    }

    //Display change course form

    public function change_course()
    {

        $user = Auth::user();

        if ($user->admission) {
            return redirect()
                ->back()
                ->with([
                    'flash' => 'Student already admitted. Unable to change course.',
                    'key' => 'error',
                ]);
        }

        $currentCourseId = $user->registered_course;

        $courses = Course::where('status', 1)->where('id', '!=', $currentCourseId)->get();

        $currentCourse = null;
        if (!empty($currentCourseId)) {
            $currentCourse = Course::find($currentCourseId);
        }

        return view('student.change-course', compact('user', 'courses', 'currentCourse'));
    }

    // Update course selection

    public function update_course(Request $request)
    {
        if (!config(ALLOW_COURSE_CHANGE, false)) {
            return redirect()->back()->with([
                'flash' => 'Students not allowed to change course at this time. Contact the administrators',
                'key' => 'error',
            ]);
        }
        $user = Auth::user();

        if ($user->admission) {
            return redirect()->back()->with([
                'flash' => 'Unable to change course.',
                'key' => 'error',
            ]);
        }

        $request->validate([
            'course_id' => 'required|exists:courses,id',
        ]);

        // Get course information
        // $course = Course::find($request->course_id);

        // if (!$course) {
        //     return redirect()->back()->with('error', 'Selected course not found.');
        // }


        // Update user record with course and session information
        $user->registered_course = $request->course_id; // Store course_id in exam field
        $user->save();

        return redirect()->route('student.profile')->with('success', 'Course changed successfully.');
    }


    // API function not used
    public function admit_student(Request $request)
    {
        $count = 0;
        $studentIds = $request->student_ids;

        if (empty($studentIds)) {
            return response()->json(['success' => false, 'message' => 'No students selected.'], 400);
        }

        try {
            foreach ($studentIds as $studentId) {
                $user = User::where('userId', $studentId)->first();
                if (!$user) {
                    continue;
                }

                $course = Course::find($user->registered_course);
                if (!$course) {
                    continue;
                }

                $existingAdmission = UserAdmission::where('user_id', $user->userId)->first();
                if ($existingAdmission) {
                    if (!$existingAdmission->email_sent) {
                        try {
                            Mail::to($user->email)->send(
                                new StudentAdmitted(
                                    $user
                                )
                            );
                            $existingAdmission->update(['email_sent' => now()]);
                            $count++;
                        } catch (\Throwable $mailError) {
                            \Log::error("Failed to send email to {$user->email}: " . $mailError->getMessage());
                        }
                    }
                    continue;
                }

                UserAdmission::create([
                    'user_id' => $user->userId, // Keep UUID
                    'course_id' => $course->id,
                    'email_sent' => now(),
                ]);

                try {
                    Mail::to($user->email)
                        ->bcc(env('MAIL_FROM_ADDRESS', 'no-reply@example.com'))
                        ->send(new StudentAdmitted(
                            $user,

                        ));
                } catch (\Throwable $mailError) {
                    \Log::error("Failed to send email to {$user->email}: " . $mailError->getMessage());
                }

                $count++;
            }

            return response()->json([
                'success' => true,
                'message' => "Admitted {$count} students successfully!",
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function delete_admission($user_id, Request $request)
    {
        $delete_user_admission = UserAdmission::where('user_id', $user_id)->first();

        if ($delete_user_admission) {
            $delete_user_admission->delete();
            AdmissionRejection::create([
                'user_id' => $user_id,
                'course_id' => $delete_user_admission->course_id,
                'rejected_at' => now(),
            ]);

            User::where('userId', $user_id)->update(['shortlist' => 0]);

            return response()->json(['message' => 'User admission and shortlisted deleted successfully.'], 200);
        } else {
            return response()->json(['message' => 'User admission not found.'], 404);
        }
    }

    public function get_attendance_page()
    {
        return view('student.attendance');
    }

    public function get_details_page()
    {
        $user = User::select('users.*', 'users.updated_at as user_updated', 'users.created_at as user_created', 'users.name as student_name', 'courses.*', 'course_sessions.session as selected_session', 'course_sessions.*', 'user_admission.*')
            ->where('userId', Auth::user()->userId)
            ->join('user_admission', 'user_admission.user_id', '=', 'users.userId')
            ->join('course_sessions', 'user_admission.session', '=', 'course_sessions.id')
            ->join('courses', 'user_admission.course_id', '=', 'courses.id')
            ->first();

        return view('student.id-qr', [
            'user' => $user,
        ]);
    }

    public function get_scanner_page()
    {
        return view('student.qr-scanner');
    }

    public function get_meeting_link_page()
    {
        $session = CourseSession::find(UserAdmission::where('user_id', Auth::user()->userId)->firstOrFail()->session);
        return view('student.meeting-link', [
            'session' => $session,
        ]);
    }

    public function updateDetails(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'name' => 'sometimes|string|max:255',
            'gender' => 'sometimes|in:male,female',
            'contact' => 'sometimes|string|regex:/^[1-9][0-9]{8}$/|max:10',
            'network_type' => 'sometimes|in:mtn,telecel,airteltigo',
            'card_type' => 'sometimes|in:ghcard,voters_id,drivers_license,passport',
        ];

        if ($request->input('card_type') === 'ghcard') {
            $rules['ghcard'] = 'sometimes|string|regex:/^[0-9]{9}-[0-9]{1}$/|max:16';
        } else {
            $rules['ghcard'] = 'sometimes|string|max:20';
        }

        $validatedData = $request->validate($rules, [], ['ghcard' => 'Card number']);

        if (isset($validatedData['network_type'])) {
            $user->network_type = $validatedData['network_type'];
        }

        if ($user->name && $user->ghcard && $user->gender && $user->contact) {
        } elseif ($user->name && $user->ghcard) {
            if (isset($validatedData['gender'])) {
                $user->gender = $validatedData['gender'];
            }
            if (isset($validatedData['contact'])) {
                $user->contact = '0' . $validatedData['contact'];
            }
        } else {
            if (isset($validatedData['name'])) {
                $user->name = $validatedData['name'];
            }
            if (isset($validatedData['card_type'])) {
                $user->card_type = $validatedData['card_type'];
            }
            if (isset($validatedData['ghcard'])) {
                $user->ghcard = $request->input('card_type') === 'ghcard' ? 'GHA-' . $validatedData['ghcard'] : $validatedData['ghcard'];
            }
            if (isset($validatedData['gender'])) {
                $user->gender = $validatedData['gender'];
            }
            if (isset($validatedData['contact'])) {
                $user->contact = '0' . $validatedData['contact'];
            }
        }
        $user->save();

        return redirect()
            ->back()
            ->with([
                'flash' => 'Your details have been updated successfully.',
                'key' => 'success',
            ]);
    }
}
