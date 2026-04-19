<?php

namespace App\Http\Controllers;

use App\Events\AdmissionDeleted;
use App\Jobs\CreateStudentAdmissionJob;
use App\Jobs\TestSubmittedJob;
use App\Models\AdmissionRejection;
use App\Models\AppConfig;
use App\Models\Branch;
use App\Models\Centre;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Notification;
use App\Models\Oex_exam_master;
use App\Models\Oex_question_master;
use App\Models\Oex_result;
use App\Models\OexExamMaster;
use App\Models\OexQuestionMaster;
use App\Models\Questionnaire;
use App\Models\QuestionnaireResponse;
use App\Models\User;
use App\Models\user_exam;
use App\Models\UserAdmission;
use App\Models\UserAssessment;
use App\Services\GhanaCardService;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class StudentOperation extends Controller
{
    // student dashboard
    public function dashboard()
    {
        $user = Auth::user();
        $isOnWaitlist = !$user->registered_course
            && \App\Models\AdmissionWaitlist::where('user_id', $user->userId)
                ->whereIn('status', ['pending', 'notified'])
                ->exists();

        $questionnaires = Questionnaire::where('active', true)
            ->latest()
            ->get(['id', 'title', 'code'])
            ->map(function ($q) use ($user) {
                $q->is_submitted = $user->questionnaire_response()
                    ->where('questionnaire_id', $q->id)
                    ->where('is_submitted', true)
                    ->exists();

                return $q;
            });

        $registeredCourse = null;
        $cohort = null;
        $centre = null;

        $isEnrolled = (bool) $user->registered_course;
        $showPlacementDetails = $isEnrolled;

        // Determine the course to display: registered course, or waitlisted course
        $courseId = $user->registered_course;

        if (!$courseId && $isOnWaitlist) {
            $courseId = \App\Models\AdmissionWaitlist::where('user_id', $user->userId)
                ->whereIn('status', ['pending', 'notified'])
                ->value('course_id');
        }

        if ($courseId) {
            $fullCourse = Course::with(['centre.branch', 'batch', 'programme'])
                ->find($courseId);

            if ($fullCourse) {
                // For waitlisted students (not enrolled), show only the programme name
                $courseName = $fullCourse->course_name;
                if (!$isEnrolled && $isOnWaitlist && $fullCourse->programme) {
                    $courseName = $fullCourse->programme->title;
                }

                $registeredCourse = [
                    'id' => $fullCourse->id,
                    'course_name' => $courseName,
                ];

                if ($showPlacementDetails && $fullCourse->batch) {
                    $cohort = [
                        'id' => $fullCourse->batch->id,
                        'title' => $fullCourse->batch->title,
                        'batch_number' => $fullCourse->batch->batch_number,
                        'year' => $fullCourse->batch->year,
                        'start_date' => $fullCourse->batch->start_date,
                        'end_date' => $fullCourse->batch->end_date,
                    ];
                }

                if ($showPlacementDetails && $fullCourse->centre) {
                    $centre = [
                        'id' => $fullCourse->centre->id,
                        'title' => $fullCourse->centre->title,
                        'gps_address' => $fullCourse->centre->gps_address,
                        'gps_location' => $fullCourse->centre->gps_location,
                        'is_pwd_friendly' => $fullCourse->centre->is_pwd_friendly,
                        'region' => $fullCourse->centre->branch?->title,
                    ];
                }
            }
        }

        // Compute waitlist position if on waitlist
        $waitlistPosition = null;
        if ($isOnWaitlist) {
            $entry = \App\Models\AdmissionWaitlist::where('user_id', $user->userId)
                ->whereIn('status', ['pending', 'notified'])
                ->first();

            if ($entry) {
                $waitlistPosition = \App\Models\AdmissionWaitlist::where('course_id', $entry->course_id)
                    ->whereIn('status', ['pending', 'notified'])
                    ->where('created_at', '<=', $entry->created_at)
                    ->count();
            }
        }

        $userAdmission = UserAdmission::where('user_id', $user->userId)->first();

        return Inertia::render('Student/Dashboard', [
            'questionnaires' => $questionnaires,
            'registeredCourse' => $registeredCourse,
            'cohort' => $cohort,
            'centre' => $centre,
            'waitlistPosition' => $waitlistPosition,
            'userAdmission' => $userAdmission ? [
                'id' => $userAdmission->id,
                'confirmed' => (bool) $userAdmission->confirmed,
            ] : null,
        ]);
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

    public function application_status()
    {
        $user = Auth::guard('web')->user();

        $user_admission = UserAdmission::where('user_id', $user->userId)->first();
        $user_assessment = UserAssessment::where('user_id', $user->id)->first();

        $userFields = ['id', 'name', 'registered_course', 'shortlist', 'application_review_completed_at'];
        if (config(SHOW_STUDENT_LEVEL, false)) {
            $userFields[] = 'student_level';
        }

        $verificationStatus = app(GhanaCardService::class)->buildStatus($user);

        return Inertia::render('Student/ApplicationStatus', [
            'user' => $user->only($userFields),
            'user_admission' => $user_admission,
            'user_assessment' => $user_assessment ? $user_assessment->only(['id', 'completed']) : null,
            'verification_status' => $verificationStatus,
        ]);
    }

    public function application_review()
    {
        $user = Auth::guard('web')->user();

        $completed = $user->application_review_completed_at !== null;

        $rawReview = trim((string) AppConfig::getValue(APPLICATION_REVIEW_IFRAME_URL, ''));
        if ($rawReview === '') {
            $rawReview = trim((string) config('app.application_review_embed_url', ''));
        }

        $reviewBase = $this->resolveApplicationReviewIframeBase($rawReview);

        $parentOrigin = rtrim((string) config('app.url', ''), '/');

        $embedUrl = null;
        if ($reviewBase !== null && $reviewBase !== '') {
            $token = app(JwtService::class)->generate($user->id);
            $embedUrl = $reviewBase.(str_contains($reviewBase, '?') ? '&' : '?')
                .http_build_query([
                    'embed' => '1',
                    'parent_origin' => $parentOrigin,
                    'token' => $token,
                ]);
        }

        return Inertia::render('Student/ApplicationReview', [
            'application_review_embed_url' => $embedUrl,
            'application_review_embed_available' => $embedUrl !== null,
            'application_review_completed' => $completed,
            'application_review_completed_at' => $user->application_review_completed_at?->toIso8601String(),
        ]);
    }

    public function complete_application_review()
    {
        $user = Auth::guard('web')->user();

        if ($user->application_review_completed_at) {
            return redirect()->route('student.level-assessment');
        }

        activity('student')
            ->causedBy($user)
            ->event('Application review acknowledged')
            ->log("{$user->name} acknowledged the application review step.");

        $user->application_review_completed_at = now();
        $user->save();

        return redirect()
            ->route('student.level-assessment')
            ->with([
                'flash' => 'Thank you. You can now continue with the level assessment.',
                'key' => 'success',
            ]);
    }

    public function verification()
    {
        $user = Auth::guard('web')->user();
        $verificationStatus = app(GhanaCardService::class)->buildStatus($user);
        $token = app(JwtService::class)->generate($user->id);

        $verifyBaseUrl = rtrim((string) config('app.quiz_frontend_url', ''), '/');
        $parentOrigin = rtrim((string) config('app.url', ''), '/');
        $embedUrl = $verifyBaseUrl !== ''
            ? "$verifyBaseUrl/verify-user?ghcard_number=" . urlencode($user->ghcard) . '&token=' . urlencode($token) . '&embed=1&parent_origin=' . urlencode($parentOrigin)
            : null;

        return Inertia::render('Student/Verification', [
            'verification_status' => $verificationStatus,
            'verification_embed_url' => $embedUrl,
            'verification_embed_available' => !empty($embedUrl),
        ]);
    }

    public function verification_status()
    {
        $user = Auth::guard('web')->user();
        $status = app(GhanaCardService::class)->buildStatus($user);

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    public function verification_image()
    {
        $user = Auth::guard('web')->user();
        $status = app(GhanaCardService::class)->buildStatus($user);

        if (!data_get($status, 'image.available')) {
            abort(404);
        }

        $disk = data_get($status, 'image.storage_disk');
        $path = data_get($status, 'image.storage_path');

        if (!Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        if (method_exists(Storage::disk($disk), 'temporaryUrl')) {
            try {
                return redirect(Storage::disk($disk)->temporaryUrl($path, now()->addMinutes(15)));
            } catch (\Exception $e) {
                Log::warning("Failed to generate temporary URL for verification image: " . $e->getMessage());
            }
        }

        return response(Storage::disk($disk)->get($path))
            ->header('Content-Type', Storage::disk($disk)->mimeType($path));
    }

    public function level_assessment()
    {
        return Inertia::render('Student/LevelAssessment');
    }

    // Exam page
    public function exam()
    {
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

    // join exam page
    public function join_exam($id)
    {

        $user = Auth::guard('web')->user();
        $eligibilityStatus = $user->examEligibilityStatus($id);
        if (!$eligibilityStatus['status']) {
            return redirect(route('student.exam.index'))->with([
                'flash' => $eligibilityStatus['message'],
                'key' => 'error',
            ]);
        }
        $exam = Oex_exam_master::where('id', $id)->get()->first();
        $questions = [];
        $usedTime = $eligibilityStatus['usedTime'] ?? 0;

        return Inertia::render('Student/Exam/JoinExam', compact('questions', 'exam', 'usedTime'));
    }

    // start exam
    public function start_exam(Request $request)
    {
        $id = $request->exam_id;
        $user = Auth::guard('web')->user();
        $eligibilityStatus = $user->examEligibilityStatus($id);

        if (!$eligibilityStatus['status']) {
            return response()->json([
                'status' => 'false',
                'message' => $eligibilityStatus['message'],
            ]);
        }

        $user_exam = user_exam::where('exam_id', $id)
            ->where('user_id', $user->id)
            ->first();

        $courseTags = collect();
        if ($user->course) {
            $courseTags = $user->course->tags->pluck('id');
            if ($courseTags->isEmpty() && $user->course->programme) {
                $courseTags = $user->course->programme->programmeTags->pluck('id');
            }
        }

        $courseTags = $courseTags->unique();

        $tagSetIds = collect();
        if ($courseTags->isNotEmpty()) {
            $tagSetIds = OexQuestionMaster::where('exam_id', $id)
                ->whereHas('tags', function ($q) use ($courseTags) {
                    $q->whereIn('tags.id', $courseTags);
                })
                ->distinct()
                ->pluck('exam_set_id');
        }

        $randomExamId = null;

        if ($tagSetIds->isNotEmpty()) {
            $randomExamId = $tagSetIds->random();
        }

        $questions = collect();

        if ($randomExamId) {
            $questions = OexQuestionMaster::select(
                [
                    'id',
                    'exam_set_id',
                    'questions',
                    'options',
                ]
            )
                ->where('exam_id', $id)
                ->where('exam_set_id', $randomExamId)
                ->inRandomOrder()
                ->get();
        }

        $examMaster = OexExamMaster::find($id);
        $questionsNeeded = $examMaster->number_of_questions;

        if ($questionsNeeded) {
            if ($questions->count() > $questionsNeeded) {
                $questions = $questions->random($questionsNeeded);
            } elseif ($questions->count() < $questionsNeeded) {
                $needed = $questionsNeeded - $questions->count();

                $generalQuestions = OexQuestionMaster::select(
                    [
                        'id',
                        'exam_set_id',
                        'questions',
                        'options',
                    ]
                )
                    ->where('exam_id', $id)
                    ->doesntHave('tags')
                    ->inRandomOrder()
                    ->limit($needed)
                    ->get();

                $questions = $questions->merge($generalQuestions);

                $questions = $questions->shuffle();
            }
        }

        if ($questions->isEmpty()) {
            return response()->json([
                'status' => 'false',
                'message' => 'No questions found for this exam.',
            ]);
        }

        if ($user_exam && !$user_exam->started) {
            $user_exam->update(['started' => Carbon::now()->toDateTimeString()]);
        }
        $data = ['status' => 'true', 'message' => 'started successfully'];
        $data['questions'] = $questions;

        $data = ['status' => 'true', 'message' => 'started successfully'];
        $data['questions'] = $questions;

        return response()->json($data);
    }

    // On submit
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
                'flash' => "Test already submitted on this exam. Submission Date: {$std_info->submitted} .Result: {$percentage}% ({$yes_ans}/{$total})",
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
                $q = OexQuestionMaster::where('id', $data['question' . $i])
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

        $res = new Oex_result;
        $res->exam_id = $request->exam_id;
        $res->user_id = $user->id;
        $res->yes_ans = $yes_ans;
        $res->no_ans = $no_ans;
        $res->result_json = json_encode($result);
        $total = $yes_ans + $no_ans;
        $res->exam_set = $exam_set_id;
        $res->save();
        NotificationController::notify(
            $user->id,
            'AFTER_EXAM_SUBMISSION_EMAIL',
            'Exams submitted successfully',
            'Hi, <br>We acknowledge your assessment test submission.<br>Please note that shortlisted applicants will be contacted as soon as possible.'
        );
        TestSubmittedJob::dispatch($user, $res);

        activity('exam')
            ->causedBy($user)
            ->withProperties(['exam_id' => $request->exam_id])
            ->event('Exam submitted')
            ->log("$user->name submitted the exam at $std_info->submitted with score $res->yes_ans/$total");

        return redirect(route('student.exam.index'));
    }

    // Applying for exam
    public function apply_exam($id)
    {
        $checkuser = user_exam::where('user_id', Auth::guard('web')->user()->id)
            ->where('exam_id', $id)
            ->get()
            ->first();

        if ($checkuser) {
            $arr = ['status' => 'false', 'message' => 'Already applied, see your exam section'];
        } else {
            $exam_user = new user_exam;

            $exam_user->user_id = Auth::guard('web')->user()->id;
            $exam_user->exam_id = $id;
            $exam_user->std_status = 1;
            $exam_user->exam_joined = 0;

            $exam_user->save();

            $arr = ['status' => 'true', 'message' => 'applied successfully', 'reload' => url('student/dashboard')];
        }

        echo json_encode($arr);
    }

    // View Result
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
            ->latest()
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

    // View answer
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
        $user = Auth::guard('web')->user();

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
            }
            $courseDetails = Course::find($admission->course_id);
            $session = CourseSession::where('course_id', $courseDetails->id)->where('id', $data['session_id'])->first();

            if (!$session) {
                return redirect()->back()->with([
                    'flash' => 'Unable to confirm session. Try again later',
                    'key' => 'error',
                ]);
            }

            $slotLeft = $session->slotLeft();

            if ($slotLeft < 1) {
                return redirect()->back()->with([
                    'flash' => 'Unable to confirm session. No slots available',
                    'key' => 'error',
                ]);
            }

            $admission->confirmed = now();
            $admission->session = $session->id;
            $admission->save();

            if (!$changingSession) {
                AdmitStudentJob::dispatch($admission);
                activity('user_admission')
                    ->causedBy($user)
                    ->performedOn($admission)
                    ->withProperties([
                        'session' => $session->name,
                        'course' => $courseDetails->course_name,
                    ])
                    ->event('Session Confirmed')
                    ->log("$user->name confirmed their session: {$session->name}");
            } else {
                activity('user_admission')
                    ->causedBy($user)
                    ->performedOn($admission)
                    ->withProperties([
                        'session' => $session->name,
                        'course' => $courseDetails->course_name,
                    ])
                    ->event('Session Changed')
                    ->log("$user->name changed their session to: {$session->name}");
            }

            return redirect()->back()->with([
                'flash' => $changingSession ? 'Session changed successfully' : 'Confirmation successful',
                'key' => 'success',
            ]);
        } catch (\Exception $e) {
            Log::error($e);

            return redirect()->back()->with([
                'flash' => 'Unable to confirm session. No slots available. Refresh page and try again later',
                'key' => 'error',
            ]);
        }
    }

    // Display change course form

    public function change_course()
    {
        $user = Auth::guard('web')->user();

        if ($user->shortlist) {
            return redirect()
                ->route('student.dashboard')
                ->with([
                    'flash' => 'Your course selection is now locked because you have been shortlisted. If you need assistance, please contact support.',
                    'key' => 'info',
                ]);
        }

        if (!$user->userAssessment?->completed) {
            return redirect()
                ->route('student.application-status')
                ->with([
                    'flash' => 'Please complete the Level Determination Assessment first.',
                    'key' => 'info',
                ]);
        }

        $currentCourse = Course::find($user->registered_course);

        return Inertia::render('Student/ChangeCourse', [
            'user' => $user,
            'currentCourse' => $currentCourse,
        ]);
    }

    // Select training center
    public function select_center($branch_id)
    {
        $user = Auth::guard('web')->user();

        $branch = Branch::find($branch_id);
        if (!$branch) {
            return redirect()->back()->with('error', 'Branch not found');
        }

        $centres = Centre::where('branch_id', $branch_id)->where('status', 1)->get();

        return Inertia::render('Student/Course/TrainingCenter', compact('user', 'branch', 'centres'));
    }

    // Select course
    public function select_course(Request $request)
    {
        $user = Auth::guard('web')->user();

        $branchId = $request->query('branch_id');
        $centreId = $request->query('centre_id');

        $branch = Branch::find($branchId);
        $centre = Centre::find($centreId);

        if (!$branch || !$centre) {
            return redirect()->back()->with('error', 'Invalid selection');
        }

        // Fetch courses available at this centre through batches
        $courses = Course::whereHas('batches', function ($query) use ($centreId) {
            $query->where('centre_id', $centreId)
                ->where('status', 1)
                ->where('completed', 0);
        })
            ->where('status', 1)
            ->get();

        return Inertia::render('Student/Course/SelectCourse', compact('user', 'branch', 'centre', 'courses'));
    }

    // Update course selection
    public function update_course(Request $request)
    {
        if (!config('ALLOW_COURSE_CHANGE', false)) {
            return redirect()
                ->back()
                ->with([
                    'key' => 'error',
                    'flash' => 'Students not allowed to change course at this time. Contact the administrators.',
                ]);
        }

        $user = Auth::guard('web')->user();

        if (!$user->userAssessment?->completed) {
            return redirect()
                ->route('student.application-status')
                ->with([
                    'flash' => 'Please complete the Level Determination Assessment first.',
                    'key' => 'info',
                ]);
        }

        if ($user->shortlist || $user->admission) {
            return redirect()
                ->route('student.application-status')
                ->with([
                    'flash' => 'Unable to change course. Selection is locked.',
                    'key' => 'error',
                ]);
        }

        $request->validate(
            [
                'course_id' => 'required|exists:courses,id',
                'branch_id' => 'required|exists:branches,id',
                'centre_id' => 'required|exists:centres,id',
            ],
            [],
            ['course_id' => 'course']
        );

        // Get course information
        $oldCourse = Course::find($user->registered_course);
        $newCourse = Course::find($request->course_id);

        if (!$newCourse) {
            return redirect()
                ->back()
                ->with([
                    'flash' => 'Selected course not found.',
                    'key' => 'error',
                ]);
        }
        activity()->withoutLogs(function () use ($user, $request) {
            $user->registered_course = $request->course_id;
            $user->save();
        });

        activity('student')
            ->causedBy($user)
            ->event('Course Changed')
            ->withProperties([
                'old_course' => $oldCourse?->course_name,
                'new_course' => $newCourse->course_name,
            ])
            ->log("{$user->name} changed their course from {$oldCourse?->course_name} to {$newCourse->course_name}");

        NotificationController::notify(
            $user->id,
            'COURSE_SELECTION',
            'Course Selected',
            'You have successfully selected <strong>' . e($newCourse->course_name) . '</strong>. You will be notified of next steps.'
        );

        return redirect()->route('student.application-status');
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
            activity('user_admission')
                ->causedBy($user)
                ->event('Admission Created')
                ->log("Admitted {$count} students successfully!");

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
            $courseId = (int) $delete_user_admission->course_id;

            activity()->withoutLogs(function () use ($delete_user_admission, $user) {
                $delete_user_admission->delete();
                $user->update([
                    'shortlist' => 0,
                    'registered_course' => null,
                ]);
            });

            // Clean up any stale waitlist entries for this user
            \App\Models\AdmissionWaitlist::where('user_id', $user->userId)
                ->whereIn('status', ['pending', 'notified'])
                ->update(['status' => 'removed']);

            AdmissionRejection::create([
                'user_id' => $user->userId,
                'course_id' => $courseId,
                'rejected_at' => now(),
            ]);

            event(new AdmissionDeleted($courseId));

            activity('user_admission')
                ->causedBy($user)
                ->event('Admission Deleted')
                ->log("$user->name deleted admission successfully!");

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
        ];

        // Default card_type to ghcard if not provided, or keep existing
        $cardType = $request->input('card_type', $user->card_type ?: 'ghcard');

        if ($cardType === 'ghcard') {
            $rules['ghcard'] = ['sometimes', 'string', 'regex:/^GHA-[0-9]{9}-[0-9]{1}$/', 'max:16', Rule::unique('users', 'ghcard')->ignore($user->id)];

            // Only prepend 'GHA-' if it's missing to avoid 'GHA-GHA-...'
            $ghValue = $request->input('ghcard');
            if (!empty($ghValue) && !str_starts_with($ghValue, 'GHA-')) {
                $request->merge(['ghcard' => 'GHA-' . $ghValue]);
            }
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
        activity()->withoutLogs(function () use ($user) {
            $user->save();
        });
        activity('student')
            ->causedBy($user)
            ->event('Details Updated')
            ->log("{$user->name}'s details updated successfully!");

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
                    $customMessages["{$fieldKey}.required"] = 'This field is required.';
                }

                switch ($field['type']) {
                    case 'text':
                    case 'textarea':
                        $rules[] = 'string';
                        $customMessages["{$fieldKey}.string"] = 'This field must be a string.';
                        break;

                    case 'radio':
                    case 'select':
                        $rules[] = 'string';
                        $customMessages["{$fieldKey}.string"] = 'This field must be a valid option.';
                        break;

                    case 'checkbox':
                        $rules[] = 'array';
                        $customMessages["{$fieldKey}.array"] = 'This field must be an array.';
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

    // Notifications
    public function notifications()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('Student/Notifications/Index', compact('notifications'));
    }

    public function markNotificationAsRead($id)
    {
        $notification = Notification::where('user_id', Auth::id())->findOrFail($id);
        $notification->update(['read_at' => now()]);

        return redirect()->back();
    }

    public function markAllNotificationsAsRead()
    {
        Notification::where('user_id', Auth::id())
            ->unread()
            ->update(['read_at' => now()]);

        return redirect()->back();
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

    public function fetch_assessment_question(Request $request)
    {
        $user = $request->user();

        $assessment = UserAssessment::firstOrCreate(
            ['user_id' => $user->id],
            [
                'current_level' => 'Beginner',
                'questions_answered' => 0,
                'correct_answers' => 0,
                'wrong_answers' => 0,
                'answered_question_ids' => [],
            ]
        );

        if ($assessment->completed) {
            return response()->json([
                'status' => 'completed',
                'message' => 'Assessment already completed.',
                'user_level' => $user->student_level,
            ]);
        }

        $timeRemainingSeconds = $this->getAssessmentTimeRemaining($assessment);

        if ($timeRemainingSeconds <= 0) {
            $this->completeAssessment($user, $assessment, false);

            return response()->json([
                'status' => 'error',
                'message' => 'Time limit exceeded! You have failed this level.',
                'level_complete' => true,
                'passed_level' => false,
                'user_overall_level' => $user->student_level,
            ], 403);
        }
        $level = $assessment->current_level;
        $answeredIds = $assessment->answered_question_ids ?? [];

        $question = OexQuestionMaster::whereHas('tags', function ($query) use ($level) {
            $query->where('name', $level);
        })
            ->whereNotIn('id', $answeredIds)
            ->inRandomOrder()
            ->first();

        if (!$question) {
            // complete assessment
            $this->completeAssessment($user, $assessment, false);

            return response()->json([
                'status' => 'completed',
                'message' => 'Assessment already completed.',
                'user_level' => $user->student_level,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'question' => [
                'id' => $question->id,
                'question' => $question->questions,
                'options' => $question->options,
                'level' => $level,
                'progress' => $assessment->questions_answered + 1,
                'total_level_questions' => config('ASSESSMENT_MAX_QUESTIONS', 10),
                'time_remaining_seconds' => $timeRemainingSeconds,
            ],
            'violation_count' => $assessment->violation_count,
        ]);
    }

    public function submit_assessment_answer(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'question_id' => 'required|exists:oex_question_masters,id',
            'answer' => 'required|string',
            'user_id' => 'sometimes|exists:users,userId',
        ]);

        $assessment = UserAssessment::where('user_id', $user->id)
            ->where('completed', false)
            ->first();

        if (!$assessment) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active assessment found.',
            ], 404);
        }

        $timeRemainingSeconds = $this->getAssessmentTimeRemaining($assessment);

        if ($timeRemainingSeconds <= 0) {
            $this->completeAssessment($user, $assessment, false);

            return response()->json([
                'status' => 'error',
                'message' => 'Time limit exceeded! You have failed this level.',
                'level_complete' => true,
                'passed_level' => false,
                'user_overall_level' => $user->student_level,
                'assessment_completed' => true,
            ], 400);
        }

        $question = OexQuestionMaster::find($request->question_id);

        $answeredIds = $assessment->answered_question_ids ?? [];
        if (in_array($question->id, $answeredIds)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Question already answered.',
            ], 400);
        }

        $answeredIds[] = $question->id;
        $assessment->answered_question_ids = $answeredIds;
        $assessment->questions_answered += 1;

        $isCorrect = ($question->ans === $request->answer);

        if ($isCorrect) {
            $assessment->correct_answers += 1;
        } else {
            $assessment->wrong_answers += 1;
        }

        $maxQuestions = config(ASSESSMENT_MAX_QUESTIONS, 10);
        $passingScore = config(ASSESSMENT_PASSING_SCORE, 8);

        $questionsLeft = $maxQuestions - $assessment->questions_answered;
        $maxPossibleScore = $assessment->correct_answers + $questionsLeft;

        $levelComplete = false;
        $passedLevel = false;

        if ($assessment->correct_answers >= $passingScore) {
            $levelComplete = true;
            $passedLevel = true;
        } elseif ($maxPossibleScore < $passingScore) {
            $levelComplete = true;
            $passedLevel = false;
        } elseif ($assessment->questions_answered >= $maxQuestions) {
            $levelComplete = true;
            $passedLevel = false;
        }

        if ($levelComplete) {
            if ($passedLevel) {
                if ($assessment->current_level === 'Beginner') {
                    $user->student_level = 'Beginner';
                    $user->save();

                    $assessment->current_level = 'Intermediate';
                    $assessment->level_started_at = null;
                    $assessment->questions_answered = 0;
                    $assessment->correct_answers = 0;
                    $assessment->wrong_answers = 0;
                } elseif ($assessment->current_level === 'Intermediate') {
                    $user->student_level = 'Intermediate';
                    $user->save();

                    $assessment->current_level = 'Advanced';
                    $assessment->level_started_at = null;
                    $assessment->questions_answered = 0;
                    $assessment->correct_answers = 0;
                    $assessment->wrong_answers = 0;
                } elseif ($assessment->current_level === 'Advanced') {
                    $this->completeAssessment($user, $assessment, true);
                }
            } else {
                $this->completeAssessment($user, $assessment, false);
            }
        }

        $assessment->save();

        $new_question = $this->fetch_assessment_question($request);

        return response()->json([
            'status' => 'success',
            'is_correct' => $isCorrect,
            'level_complete' => $levelComplete,
            'passed_level' => $passedLevel,
            'assessment_completed' => $assessment->completed,
            'current_level' => $assessment->current_level,
            'user_overall_level' => $user->student_level,
            'next_question' => $new_question->original['question'] ?? null,
        ]);
    }

    public function record_assessment_violation(Request $request)
    {
        $user = $request->user();

        $assessment = UserAssessment::where('user_id', $user->id)
            ->where('completed', false)
            ->first();

        if (!$assessment) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active assessment found.',
            ], 404);
        }

        $assessment->violation_count += 1;
        $maxViolations = config('ASSESSMENT_MAX_VIOLATIONS', 3);

        if ($assessment->violation_count >= $maxViolations) {
            $this->completeAssessment($user, $assessment, false);

            return response()->json([
                'status' => 'error',
                'message' => 'Maximum violations reached! Assessment auto-submitted.',
                'violation_count' => $assessment->violation_count,
                'assessment_completed' => true,
                'user_overall_level' => $user->student_level,
            ]);
        }

        $assessment->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Violation recorded.',
            'violation_count' => $assessment->violation_count,
            'assessment_completed' => false,
        ]);
    }

    private function getAssessmentTimeRemaining($assessment)
    {
        if (is_null($assessment->level_started_at)) {
            $assessment->level_started_at = now();
            $assessment->save();
        }

        $timeoutSeconds = config('ASSESSMENT_LEVEL_TIMEOUT_SECONDS', 900);
        $timeElapsedSeconds = now()->getTimestamp() - $assessment->level_started_at->getTimestamp();

        return $timeoutSeconds - $timeElapsedSeconds;
    }

    private function completeAssessment($user, $assessment, $passed = false)
    {
        $assessment->completed = true;

        if ($passed) {
            $user->student_level = 'Advanced';
        } else {
            if ($assessment->current_level === 'Beginner') {
                $user->student_level = 'Beginner';
            } elseif ($assessment->current_level === 'Intermediate') {
                $user->student_level = 'Beginner';
            } elseif ($assessment->current_level === 'Advanced') {
                $user->student_level = 'Intermediate';
            }
        }

        $user->save();
        $assessment->save();

        NotificationController::notify(
            $user->id,
            'ASSESSMENT',
            'Assessment Completed',
            'You have completed your level determination assessment. Your level has been set to <strong>' . e($user->student_level) . '</strong>.'
        );

        activity('assessment')
            ->causedBy($user)
            ->performedOn($assessment)
            ->withProperties([
                'level' => $user->student_level,
                'correct_answers' => $assessment->correct_answers,
                'wrong_answers' => $assessment->wrong_answers,
                'violation_count' => $assessment->violation_count,
            ])
            ->event('Assessment Completed')
            ->log("{$user->name} completed the level determination assessment at level: {$user->student_level}");
    }

    public function recommendCourses(Request $request)
    {
        // $user = $request->user();

        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,userId',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $user = User::where('userId', $data['userId'])->first();

        if ($user->isAdmitted() && $user->hasAttendance()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not allowed to access this resource.',
            ], 403);
        }

        $recommendedCourses = Course::where('status', 1)
            ->whereHas('tags', function ($query) use ($user) {
                $query->whereIn('name', [$user->student_level, $user->network_type]);
            })
            ->with('tags')
            ->get();

        return response()->json([
            'status' => 'success',
            'recommended_courses' => $recommendedCourses,
        ]);
    }

    /**
     * Absolute http(s) or scheme-relative URLs are used as entered (trailing slash trimmed for query joining).
     * Path-only values (e.g. "/application-review") are prefixed with {@see config('app.quiz_frontend_url')} (same base as verification iframe).
     */
    protected function resolveApplicationReviewIframeBase(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $raw) || str_starts_with($raw, '//')) {
            return rtrim($raw, '/');
        }

        $websiteBase = rtrim((string) config('app.quiz_frontend_url', ''), '/');
        if ($websiteBase === '') {
            return null;
        }

        $path = str_starts_with($raw, '/') ? $raw : '/'.$raw;

        return rtrim($websiteBase.$path, '/');
    }
}
