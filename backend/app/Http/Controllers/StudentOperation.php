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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Jobs\AdmitStudentJob;
use App\Jobs\CreateStudentAdmissionJob;
use App\Jobs\TestSubmittedJob;
use App\Models\AdmissionRejection;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use App\Models\Questionnaire;
use App\Models\QuestionnaireResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class StudentOperation extends Controller
{
    //student dashboard
    public function dashboard()
    {
        if (!Auth::user()->isAdmitted()) {
            return redirect(route('student.profile.edit'));
        }

        // $data['portal_exams'] = user_exam::select(['user_exams.*', 'users.name', 'oex_exam_masters.*', 'oex_categories.name as category_name'])
        //     ->selectRaw('(SELECT count(id) from oex_question_masters where exam_id = oex_exam_masters.id) as question_count', [])
        //     ->join('users', 'users.id', '=', 'user_exams.user_id')
        //     ->join('oex_exam_masters', 'user_exams.exam_id', '=', 'oex_exam_masters.id')
        //     ->orderBy('user_exams.exam_id', 'desc')
        //     ->join('oex_categories', 'oex_exam_masters.category', '=', 'oex_categories.id')
        //     ->where('user_exams.user_id', Auth::user()->id)
        //     ->where('user_exams.std_status', '1')
        //     ->get()
        //     ->toArray();

        //     return view('student.dashboard', $data);

        $exams = user_exam::select(['user_exams.*', 'users.name', 'oex_exam_masters.*', 'oex_categories.name as category_name'])
            ->selectRaw('(SELECT count(id) from oex_question_masters where exam_id = oex_exam_masters.id) as question_count', [])
            ->join('users', 'users.id', '=', 'user_exams.user_id')
            ->join('oex_exam_masters', 'user_exams.exam_id', '=', 'oex_exam_masters.id')
            ->orderBy('user_exams.exam_id', 'desc')
            ->join('oex_categories', 'oex_exam_masters.category', '=', 'oex_categories.id')
            ->where('user_exams.user_id', Auth::user()->id)
            ->where('user_exams.std_status', '1')
            ->get()
            ->toArray();

        $questionnaires = Questionnaire::where('active', true)->latest()->get();

        $questionnaires = $questionnaires->map(function ($questionnaire) {
            $questionnaire['is_submitted'] = Auth::user()->questionnaire_response()->where('questionnaire_id', $questionnaire->id)->where('is_submitted', true)->exists();

            return $questionnaire;
        });
        return Inertia::render('Student/Dashboard', compact('exams', 'questionnaires'));

        // $data['portal_exams'] = Oex_exam_master::select(['oex_exam_masters.*', 'oex_categories.name as cat_name'])
        //     ->join('oex_categories', 'oex_exam_masters.category', '=', 'oex_categories.id')
        //     ->orderBy('id', 'desc')->where('oex_exam_masters.status', '1')->get()->toArray();

    }
    public function profile()
    {
        // Get the current authenticated user
        $user = Auth::guard('web')->user();

        // Get course details if available in user's record
        $course = null;
        if (!empty($user->exam)) {
            // Assuming 'exam' field in users table holds the course_id
            $course = Course::find($user->registered_course);
        }

        // Check if the user has a rejected admission
        $rejection = AdmissionRejection::where('user_id', $user->userId)->orderBy('rejected_at', 'desc')->first();

        return view('student.profile', compact('user', 'course', 'rejection'));
    }

    // application status
    public function application_status()
    {
        $user = Auth::guard('web')->user();

        $user_exam = user_exam::where('user_id', $user->id)->first();
        $user_admission = UserAdmission::where('user_id', $user->userId)->first();
        // dd($exam_submitted, $data);

        return Inertia::render('Student/ApplicationStatus', compact('user', 'user_exam', 'user_admission'));
    }

    //Exam page
    public function exam()
    {
        // Admission check removed - students can view/take exams before admission

        // $student_info = user_exam::select(['user_exams.*', 'users.name', 'oex_exam_masters.title', 'oex_exam_masters.exam_date', 'users.created_at as registered'])
        //     ->join('users', 'users.id', '=', 'user_exams.user_id')
        //     ->join('oex_exam_masters', 'user_exams.exam_id', '=', 'oex_exam_masters.id')
        //     ->orderBy('user_exams.exam_id', 'desc')
        //     ->where('user_exams.user_id', Auth::user()->id)
        //     ->where('user_exams.std_status', '1')
        //     ->get()
        //     ->toArray();

        // return view('student.exam', ['student_info' => $student_info]);

        $exams = user_exam::select(['user_exams.*', 'users.name', 'oex_exam_masters.*', 'oex_categories.name as category_name'])
            ->selectRaw('(SELECT count(id) from oex_question_masters where exam_id = oex_exam_masters.id) as question_count', [])
            ->join('users', 'users.id', '=', 'user_exams.user_id')
            ->join('oex_exam_masters', 'user_exams.exam_id', '=', 'oex_exam_masters.id')
            ->orderBy('user_exams.exam_id', 'desc')
            ->join('oex_categories', 'oex_exam_masters.category', '=', 'oex_categories.id')
            ->where('user_exams.user_id', Auth::user()->id)
            ->where('user_exams.std_status', '1')
            ->get()
            ->toArray();


        return Inertia::render('Student/Exam/Index', compact('exams'));
    }

    //join exam page
    public function join_exam($id)
    {

        $user = Auth::guard('web')->user();
        $eligibilityStatus = $user->examEligibilityStatus($id);
        // dd($question->pluck("id"));
        if (!$eligibilityStatus['status']) {
            return redirect(route('student.exam.index'))->with([
                'flash' => $eligibilityStatus['message'],
                'key' => 'error',
            ]);
        }
        // dd($question->pluck("id"));
        $exam = Oex_exam_master::where('id', $id)->get()->first();
        $questions = [];
        $usedTime = $eligibilityStatus['usedTime'] ?? 0;

        return Inertia::render('Student/Exam/JoinExam', compact('questions', 'exam', 'usedTime'));

        // return view('student.join_exam', ['question' => $questions, 'exam' => $exam, 'usedTime' => $usedTime]);
    }

    // start exam
    public function start_exam(Request $request)
    {
        $id = $request->exam_id;
        $user = Auth::guard('web')->user();
        $eligibilityStatus = $user->examEligibilityStatus($id);
        // dd($question->pluck("id"));
        if (!$eligibilityStatus['status']) {
            return redirect(route('student.exam.index'))->with([
                'flash' => $eligibilityStatus['message'],
                'key' => 'error',
            ]);
        }

        $user_exam = user_exam::where('exam_id', $id)
            ->where('user_id', Auth::guard('web')->user()->id)
            ->get()
            ->first();


        $questionSets = Oex_question_master::select('exam_set_id')->distinct()->pluck('exam_set_id');
        $randomExamId = $questionSets->random();
        $questions = Oex_question_master::select(
            [
                "id",
                "exam_set_id",
                "questions",
                "options",
            ]
        )->where('exam_set_id', $randomExamId)->inRandomOrder()->get();

        if ($user_exam && !$user_exam->started) {
            $user_exam->update(['started' => Carbon::now()->toDateTimeString()]);
        }
        $data = ['status' => 'true', 'message' => 'started successfully'];
        $data['questions'] = $questions;

        return response()->json($data);
    }

    //On submit
    public function submit_questions(Request $request)
    {
        $user = Auth::guard('web')->user();

        $std_info = user_exam::where('user_id', Auth::guard('web')->user()->id)
            ->where('exam_id', $request->exam_id)
            ->get()
            ->first();

        if ($std_info && $std_info->submitted) {
            $res = Oex_result::where('exam_id', $request->exam_id)
                ->where('user_id', $user->id)
                ->get()
                ->first();

            $yes_ans = $res->yes_ans;
            $total = $res->yes_ans + $res->no_ans;
            $percentage = round(($yes_ans / $total) * 100);

            return redirect(route('student.exam.index'))->with([
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

        $userId = $user->userId;

        $res = new Oex_result();
        $res->exam_id = $request->exam_id;
        $res->user_id = $user->id;
        $res->yes_ans = $yes_ans;
        $res->no_ans = $no_ans;
        $res->result_json = json_encode($result);
        $total = $yes_ans + $no_ans;
        $res->exam_set = $exam_set_id;
        // $percentage = $total > 0 ? round(($yes_ans / $total) * 100) : 0;
        $res->save();
        // $storedResult = Oex_result::where('user_id', $user->id)
        //     ->where('exam_id', $request->exam_id)
        //     ->first();
        // GoogleSheets::updateGoogleSheets($userId, ['result' => $storedResult->yes_ans]);
        TestSubmittedJob::dispatch($user, $res);

        return redirect(route('student.exam.index'))->with([
            // 'flash' => "Test submitted successfully. Result: {$percentage}%  {$yes_ans}/{$total}",
            'flash' => 'Test submitted successfully.',
            'key' => 'success',
        ]);
    }

    //Applying for exam
    public function apply_exam($id)
    {
        $checkuser = user_exam::where('user_id', Auth::guard('web')->user()->id)
            ->where('exam_id', $id)
            ->get()
            ->first();

        if ($checkuser) {
            $arr = ['status' => 'false', 'message' => 'Already applied, see your exam section'];
        } else {
            $exam_user = new user_exam();

            $exam_user->user_id = Auth::guard('web')->user()->id;
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
        $user = Auth::guard('web')->user();

        $exam = Oex_exam_master::where('id', $id)->first();

        if (!$exam) {
            abort(404);
        }

        $exam->formatted_exam_date = Carbon::parse($exam->exam_date)->format(config('app.fulldate_format'));

        $showResultsToStudents = config(SHOW_RESULTS_TO_STUDENTS, false);
        if (!$showResultsToStudents) {
            return redirect()
                ->route('student.results')
                ->with([
                    'flash' => 'Results for this exam are not available.',
                    'key' => 'info',
                ]);
        }

        $result = Oex_result::where('exam_id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$result) {
            return redirect()
                ->route('student.results')
                ->with([
                    'flash' => 'No result found for this exam.',
                    'key' => 'info',
                ]);
        }
        return Inertia::render('Student/Exam/Result', [
            'exam' => $exam,
            'result' => $result,
            'student' => $user,
        ]);
    }

    //View answer
    public function view_answer($id)
    {
        $data['question'] = Oex_question_master::where('exam_id', $id)->get()->toArray();

        return view('student.view_amswer', $data);
    }

    // public function reset_exam($exam_id, $user_id)
    // {
    //     $user = User::findOrFail($user_id);
    //     $user->created_at = Carbon::now()->toDateTimeString();
    //     $user->updated_at = Carbon::now()->toDateTimeString();
    //     $user->save();

    //     user_exam::updateOrCreate(
    //         [
    //             'user_id' => $user_id,
    //             'exam_id' => $exam_id,
    //         ],
    //         ['started' => null, 'submitted' => null, 'exam_joined' => 0, 'std_status' => 1],
    //     );

    //     Oex_result::where('user_id', $user_id)->where('exam_id', $exam_id)->delete();

    //     return redirect(url('admin/manage_students'))->with([
    //         'flash' => 'Exam reset successfully',
    //         'key' => 'success',
    //     ]);
    // }

    public function select_session_view(Request $request)
    {
        $user = Auth::guard('web')->user()->only(['id', 'name', 'userId']);
        $admission = UserAdmission::where('user_id', $user['userId'])->firstOrFail();
        $course = Course::find($admission->course_id);
        $sessions = CourseSession::where('course_id', $course->id)->get();

        $sessions = $sessions->map(function ($session) {
            $session->slotLeft = $session->slotLeft();
            return $session;
        });

        $session = CourseSession::where('id', $admission->session)->first();

        return Inertia::render('Student/Session', compact(
            'user',
            'admission',
            'course',
            'sessions',
            'session'
        ));
    }

    public function confirm_session(Request $request)
    {
        $user = $request->guard('web')->user();

        $data = $request->validate(
            [
                'session_id' => 'required|exists:course_sessions,id',
            ],
            [],
            [
                'session_id' => 'session',
            ]
        );

        try {
            $admission = UserAdmission::where('user_id', $user->userId)->firstOrFail();
            $changingSession = $admission->confirmed && $admission->session;

            if ($changingSession && !config(ALLOW_SESSION_CHANGE, false)) {

                return redirect()->back()->with([
                    'flash' => 'Unable to change session at this time. Contact administrator',
                    'key' => 'error',
                ]);

                // return redirect(url('student/select-session/' . $user->userId))->with([
                //     'flash' => 'Unable to change session at this time. Contact administrator',
                //     'key' => 'error',
                // ]);
            }

            $courseDetails = Course::find($admission->course_id);
            $session = CourseSession::where('course_id', $courseDetails->id)->where('id', $data['session_id'])->first();

            if (!$session) {
                // return redirect(url('student/select-session/' . $user->userId))->with([
                //     'flash' => 'Unable to confirm session. Try again later',
                //     'key' => 'error',
                // ]);

                return redirect()->back()->with([
                    'flash' => 'Unable to confirm session. Try again later',
                    'key' => 'error',
                ]);
            }

            $slotLeft = $session->slotLeft();

            if ($slotLeft < 1) {
                // return redirect(url('student/select-session/' . $user->userId))->with([
                //     'flash' => 'Unable to confirm session. No slots available',
                //     'key' => 'error',
                // ]);

                return redirect()->back()->with([
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
            // return redirect(url('student/select-session/' . $user->userId))->with([
            //     'flash' => $changingSession ? 'Session changed successfully' : 'Confirmation successful',
            //     'key' => 'success',
            // ]);

            return redirect()->back()->with([
                'flash' => $changingSession ? 'Session changed successfully' : 'Confirmation successful',
                'key' => 'success',
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            // return redirect(url('student/select-session/' . $user->userId))->with([
            //     'flash' => 'Unable to confirm session. No slots available. Refresh page and try again later',
            //     'key' => 'error',
            // ]);

            return redirect()->back()->with([
                'flash' => 'Unable to confirm session. No slots available. Refresh page and try again later',
                'key' => 'error',
            ]);

            // return response()->json([
            //     'status' => [
            //         'key' => 'error',
            //         'flash' => 'Unable to confirm session. No slots available. Refresh page and try again later'
            //     ]
            // ]);
        }
    }

    //Display change course form

    public function change_course()
    {
        $user = Auth::guard('web')->user();

        // if ($user->admission) {
        //     return redirect()
        //         ->back()
        //         ->with([
        //             'flash' => 'Student already admitted. Unable to change course.',
        //             'key' => 'error',
        //         ]);
        // }

        $currentCourseId = $user->registered_course;

        $courses = Course::where('status', 1)->where('id', '!=', $currentCourseId)->get();

        $currentCourse = null;
        if (!empty($currentCourseId)) {
            $currentCourse = Course::find($currentCourseId);
        }

        return Inertia::render('Student/ChangeCourse', compact('user', 'courses', 'currentCourse'));
        return view('student.change-course', compact('user', 'courses', 'currentCourse'));
    }

    // Update course selection

    public function update_course(Request $request)
    {
        if (!config(ALLOW_COURSE_CHANGE, false)) {
            return redirect()
                ->back()
                ->with([
                    'key' => 'error',
                    'flash' => 'Students not allowed to change course at this time. Contact the administrators.',
                ]);
        }

        $user = Auth::guard('web')->user();

        if ($user->admission) {
            return redirect()
                ->back()
                ->with([
                    'flash' => 'Unable to change course.',
                    'key' => 'error',
                ]);
        }

        $request->validate(
            [
                'course_id' => 'required|exists:courses,id',
            ],
            [],
            ['course_id' => 'course']
        );

        // Get course information
        // $course = Course::find($request->course_id);

        // if (!$course) {
        //     return redirect()->back()->with('error', 'Selected course not found.');
        // }

        // Update user record with course and session information
        $user->registered_course = $request->course_id; // Store course_id in exam field
        $user->save();

        return redirect()->route('student.profile.edit');
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
                $course = Course::findOrFail($user->registered_course);
                CreateStudentAdmissionJob::dispatch($user, $course, null);
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

    public function delete_admission(User $user)
    {
        $delete_user_admission = UserAdmission::where('user_id', $user->userId)->first();

        if ($delete_user_admission) {
            $delete_user_admission->delete();

            AdmissionRejection::create([
                'user_id' => $user->userId,
                'course_id' => $delete_user_admission->course_id,
                'rejected_at' => now(),
            ]);

            $user->update(['shortlist' => 0]);

            return Redirect::route('student.application-status');
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
            ->where('userId', Auth::guard('web')->user()->userId)
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
        $session = CourseSession::find(UserAdmission::where('user_id', Auth::guard('web')->user()->userId)->firstOrFail()->session);
        return view('student.meeting-link', [
            'session' => $session,
        ]);
    }

    public function updateDetails(Request $request)
    {
        $user = Auth::guard('web')->user();

        $rules = [
            'first_name' => 'sometimes|string|max:255',
            'middle_name' => 'sometimes|nullable|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'gender' => 'sometimes|in:male,female',
            'mobile_no' => 'sometimes|string|phone',
            'network_type' => 'sometimes|in:mtn,telecel,airteltigo',
            'card_type' => 'sometimes|in:ghcard,voters_id,drivers_license,passport',
        ];

        if ($request->input('card_type') === 'ghcard') {
            $rules['ghcard'] = ['sometimes', 'string', 'regex:/^GHA-[0-9]{9}-[0-9]{1}$/', 'max:16', Rule::unique('users', 'ghcard')->ignore($user->id)];
            $request->merge([
                'ghcard' => 'GHA-' . $request->ghcard,
            ]);
        } else {
            $rules['ghcard'] = ['sometimes', 'string', 'max:20', Rule::unique('users', 'ghcard')->ignore($user->id)];
        }

        $validatedData = $request->validate($rules, [], ['ghcard' => 'Card number']);

        if (isset($validatedData['network_type'])) {
            $user->network_type = $validatedData['network_type'];
        }

        if ($user->details_updated_at) {
            return redirect()
                ->back()
                ->with([
                    'flash' => 'Cannot update details AGAIN',
                    'key' => 'error',
                ]);
        }
        if (isset($validatedData['gender'])) {
            $user->gender = $validatedData['gender'];
        }
        if (isset($validatedData['mobile_no'])) {
            $user->mobile_no = $validatedData['mobile_no'];
        }

        // Handle separate name fields
        if (isset($validatedData['first_name']) || isset($validatedData['middle_name']) || isset($validatedData['last_name'])) {
            if (isset($validatedData['first_name'])) {
                $user->first_name = $validatedData['first_name'];
            }
            if (isset($validatedData['middle_name'])) {
                $user->middle_name = $validatedData['middle_name'];
            }
            if (isset($validatedData['last_name'])) {
                $user->last_name = $validatedData['last_name'];
            }

            // Update the name field from separate fields
            $user->setNameFromFields();
        }

        if (isset($validatedData['card_type'])) {
            $user->card_type = $validatedData['card_type'];
        }
        if (isset($validatedData['ghcard'])) {
            $user->ghcard = $validatedData['ghcard'];
        }

        $user->details_updated_at = now();
        $user->save();

        return redirect()
            ->back()
            ->with([
                'flash' => 'Your details have been updated successfully.',
                'key' => 'success',
            ]);
    }

    public function questionnaire()
    {
        $questionnaires = Questionnaire::where('active', true)->latest()->get();

        $questionnaires = $questionnaires->map(function ($questionnaire) {
            $questionnaire['is_submitted'] = Auth::guard('web')->user()->questionnaire_response()->where('questionnaire_id', $questionnaire->id)->where('is_submitted', true)->exists();

            return $questionnaire;
        });

        return Inertia::render('Student/Assessment/Index', compact('questionnaires'));

        return view('student.questionnaire', compact('questionnaires'));
    }

    public function take_questionnaire($code)
    {
        $questionnaire = Questionnaire::where('code', $code)->first();

        if (!$questionnaire) {
            return redirect(route('student.assessment.index'))->with(
                [
                    'flash' => 'Questionnaire not found.',
                    'key' => 'error',
                ]
            );
        }

        $user = Auth::guard('web')->user();

        if (!$user->isAdmitted() && !$user->hasAttendance()) {
            return redirect(route('student.assessment.index'))->with(
                [
                    'flash' => 'You are not allowed to access this form.',
                    'key' => 'error',
                ]
            );
        }

        $userQuestionnaireResponse = $user->questionnaire_response()->where('questionnaire_id', $questionnaire->id)->first();

        $hasSubmitted = $userQuestionnaireResponse->is_submitted ?? false;

        if ($hasSubmitted) {
            return redirect(route('student.assessment.index'))->with(
                [
                    'flash' => 'You have already taken this assessment.',
                    'key' => 'error',
                ]
            );
        }

        $instructors = null;

        foreach ($questionnaire->schema as $section) {
            if (strtolower($section['type']) === 'instructors') {
                $admission = $user->admission;
                $instructors = Course::find($admission->course_id)?->assignedAdmins()->get() ?? null;
            }
        }

        $responses = $userQuestionnaireResponse['response_data'] ?? [];

        $instructorQuestions = collect($questionnaire->schema)->where('type', 'instructors')->first()['questions'] ?? [];

        return Inertia::render('Student/Assessment/TakeQuestionnaire', compact('questionnaire', 'hasSubmitted', 'instructors', 'instructorQuestions', 'responses'));

        return view('student.take_questionnaire', compact('questionnaire', 'hasSubmitted', 'instructors', 'instructorQuestions', 'responses'));
    }

    public function store_questionnaire(Request $request)
    {
        $code = $request->code;
        // find index of the instructors schema
        $instructorSectionIndex = null;
        $questionnaire = Questionnaire::where('code', $code)->first();

        collect($questionnaire->schema)->each(function ($section, $index) use (&$instructorSectionIndex) {
            if ($section['type'] === 'instructors') {
                $instructorSectionIndex = $index;
            }
        });

        $sectionIndex = Str::startsWith($request->section, 'instructor-') ? $instructorSectionIndex : (int) $request->section;

        $instructorSection = collect($questionnaire->schema)->where('type', 'instructors')->first();
        $section = Str::startsWith($sectionIndex, 'instructor-') ? $instructorSection : $questionnaire->schema[$sectionIndex];
        $totalSections = count($questionnaire->schema);
        $schema = $section['questions'];


        $validationRules = [
            'response_data' => 'required|array',
        ];

        $customMessages = [
            'response_data.required' => 'The assessment responses are required.',
        ];

        $formattedData = [];
        $attributes = [];

        foreach ($request->input('response_data', []) as $key => $value) {
            foreach ($schema as $field) {
                if (strcasecmp($key, $field['title']) == 0) {
                    $formattedData[$field['field_name']] = is_array($value)
                        ? array_map('trim', $value)
                        : trim($value);

                    break;
                }
            }
        }

        $isInstructorSelect = (bool) $request->input('response_data.instructors_select') ?? false;
        $isInstructorQuestions = (bool) $request->input('instructor_id') ?? false;

        if ($isInstructorSelect) {
            $validationRules['response_data.instructors'] = 'required|array';
            $validationRules['response_data.instructors.*'] = 'exists:admins,id';

            $customMessages['response_data.instructors.required'] = 'Please select at least one instructor.';
            $customMessages['response_data.instructors.*.exists'] = 'The selected instructor is invalid.';
        } else {
            foreach ($schema as $field) {
                $fieldKey = "response_data.{$field['field_name']}";

                $rules = [];

                $attributes[$fieldKey] = Str::remove('_id', Str::remove('response_data.', $fieldKey, true));

                if (!empty($field['validators']['required'])) {
                    $rules[] = 'required';
                    $customMessages["{$fieldKey}.required"] = "This field is required.";
                }

                switch ($field['type']) {
                    case 'text':
                    case 'textarea':
                        $rules[] = 'string';
                        $customMessages["{$fieldKey}.string"] = "This field must be a string.";
                        break;

                    case 'radio':
                    case 'select':
                        $rules[] = 'string';
                        $customMessages["{$fieldKey}.string"] = "This field must be a valid option.";
                        break;

                    case 'checkbox':
                        $rules[] = 'array';
                        $customMessages["{$fieldKey}.array"] = "This field must be an array.";
                        break;


                    default:
                        $rules[] = 'nullable';
                        break;
                }

                $validationRules[$fieldKey] = implode('|', $rules);
                $additionRules = Str::length($field['rules'] ?? '') > 0 ? '|' . $field['rules'] ?? '' : '';
                $validationRules[$fieldKey] = $validationRules[$fieldKey] . $additionRules;
            }
        }

        $validated = $request->validate($validationRules, $customMessages, $attributes);

        // Load existing draft or create new one
        $draft = QuestionnaireResponse::firstOrCreate(
            [
                'questionnaire_id' => $questionnaire->id,
                'user_id' => Auth::guard('web')->user()->id,
            ],
            [
                'response_data' => [],
            ]
        );

        // Get existing data
        $existing = $draft->response_data ?? [];

        if ($isInstructorSelect) {
            // Store selected instructor IDs
            $existing['selected_instructors'] = $validated['response_data']['instructors'];
            $existing['completed_instructors'] = [];
        } elseif ($isInstructorQuestions) {
            $instructorId = $request->input('instructor_id');
            // Store the individual instructor’s responses
            $existing['instructors'][$instructorId] = $validated['response_data'];
            $existing['completed_instructors'][] = $instructorId;
        } else {
            // Store responses for other sections like facility, transport, etc.
            $existing[$section['title']] = $validated['response_data'];
        }

        // Check which instructors haven't been filled yet
        $yetToComplete = collect($existing['selected_instructors'] ?? [])->filter(function ($id) use ($existing) {
            return !in_array($id, $existing['completed_instructors'] ?? []);
        })->values()->all();

        $hasNextInstructor = ($isInstructorQuestions || $isInstructorSelect) && !empty($yetToComplete);

        // remaining sections left to complete
        $remainingSections = collect($questionnaire->schema)->filter(function ($section) use ($existing) {
            return !isset($existing[$section['title']]);
        })->keys()->all();

        $isSubmitted = count($remainingSections) === 0 && count($yetToComplete) === 0;

        // Save the updated response_data
        $draft->update([
            'response_data' => $existing,
            'is_submitted' => $isSubmitted,
        ]);

        return response()->json([
            'status' => true,
            'progress' => [
                'is_submitted' => $isSubmitted,
                'next_section' => !$isSubmitted ? ($remainingSections[0] ?? null) : null,
                'next_instructor' => $hasNextInstructor ? $yetToComplete[0] : false,
                'instructor_section' => $hasNextInstructor ? $sectionIndex : false,
                'instructor_button_text' => ($sectionIndex >= $totalSections - 1 && count($yetToComplete) === 1) ? 'Submit' : 'Save & Next',
            ],
        ]);
    }

    // Student results page
    public function results()
    {
        $user = Auth::guard('web')->user();
        $results = \DB::table('user_exams')
            ->join('oex_exam_masters', 'user_exams.exam_id', '=', 'oex_exam_masters.id')
            ->leftJoin('oex_results', function ($join) {
                $join->on('user_exams.exam_id', '=', 'oex_results.exam_id')
                    ->on('user_exams.user_id', '=', 'oex_results.user_id');
            })
            ->where('user_exams.user_id', $user->id)
            ->select([
                'oex_exam_masters.title as exam_title',
                'oex_exam_masters.exam_date',
                'oex_exam_masters.exam_duration',
                'user_exams.started',
                'user_exams.submitted',
                'oex_results.yes_ans',
                'oex_results.no_ans',
                'oex_results.result_json',
                'oex_results.created_at as result_created',
            ])
            ->orderByDesc('oex_exam_masters.exam_date')
            ->get();

        return Inertia::render('Student/Results', [
            'results' => $results,
        ]);
    }
}
