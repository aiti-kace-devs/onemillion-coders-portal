<?php

namespace App\Http\Controllers;

use App\Jobs\CreateStudentAdmissionJob;
use Yajra\DataTables\Facades\DataTables;
use App\Events\UserRegistered;
use App\Jobs\AddNewStudentsJob;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessStudentRegistrationJob;
use App\Jobs\UpdateSheetWithGhanaCardDetails;
use App\Jobs\AdmitStudentJob;
use App\Models\Attendance;
use App\Models\CourseSession;
use Illuminate\Http\Request;
use App\Models\Oex_category;
use App\Models\SmsTemplate;
use App\Models\Oex_exam_master;
use App\Models\Oex_student;
use App\Models\Oex_portal;
use App\Models\User;
use App\Models\Oex_question_master;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\Models\user_exam;
use App\Models\Admin;
use App\Models\FormResponse;
use App\Models\Oex_result;
use App\Models\UserAdmission;
use App\Mail\ExamLoginCredentials;
use App\Mail\StudentAdmitted;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Models\AdmissionRejection;
use App\Helpers\GoogleSheets;
use App\Models\Course;
use App\Models\Branch;
use App\Models\Centre;
use App\Models\Programme;
use App\Helpers\Common as CommonHelper;
use App\Helpers\MailerHelper;
use App\Jobs\SendBulkEmailJob;
use App\Jobs\SendBulkSMSJob;
use Carbon\Carbon;

class AdminController extends Controller
{
    use CommonHelper;
    // admin dashboard
    public function index()
    {
        $user_count = User::count();
        $shortlist_count = User::where('shortlist', 1)->count();
        $admin_count = Admin::count();
        $user_admission_count = UserAdmission::whereNotNull('session')->count();
        $programe_count = Programme::count();

        $studentsPerRegion = DB::table('users')
            ->join('courses', 'users.registered_course', '=', 'courses.id')
            ->select('courses.location as region', DB::raw('count(*) as total'))
            ->whereNotNull('users.registered_course')
            ->groupBy('courses.location')
            ->get();

        $studentsPerCourse = User::select('courses.course_name', DB::raw('count(*) as total'))
            ->leftJoin('courses', 'users.registered_course', '=', 'courses.id')
            ->whereNotNull('registered_course')
            ->groupBy('courses.course_name')
            ->get();

        $studentsPerCourse = $studentsPerCourse->map(function ($item) {
            $parts = explode(" - ", $item->course_name);
            $item->display_name = $parts[0];
            return $item;
        });

        $registrationsPerDay = User::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->limit(30)
            ->get();

        $genderDistribution = User::select('gender', DB::raw('count(*) as total'))
            ->whereNotNull('gender')
            ->groupBy('gender')
            ->get();

        $ageGroups = User::select(
            DB::raw('CASE
                               WHEN age BETWEEN 15 AND 19 THEN "15-19"
                               WHEN age BETWEEN 20 AND 24 THEN "20-24"
                               WHEN age BETWEEN 25 AND 35 THEN "25-35"
                               WHEN age BETWEEN 36 AND 45 THEN "36-45"
                               WHEN age >= 45 THEN "45+"
                               ELSE "Unknown"
                            END as age_group'),
            DB::raw('count(*) as total')
        )
            ->groupBy('age_group')
            ->orderBy(DB::raw('MIN(age)'))
            ->get();

        //  $admittedstudentsPerRegion = DB::table('users')
        //                     ->join('courses', 'users.registered_course', '=', 'courses.id')
        //                      ->select('courses.location as region', DB::raw('count(*) as total'))
        //                      ->whereNotNull('users.registered_course')
        //                       ->groupBy('courses.location')
        //                      ->get();

        $admittedstudentsPerRegion = DB::table('user_admission')
            ->join('courses', 'user_admission.course_id', '=', 'courses.id')
            ->select('courses.location as region', DB::raw('count(*) as total'))
            ->whereNotNull('user_admission.course_id')
            ->whereNotNull('user_admission.session')
            ->groupBy('courses.location')
            ->get();



        return view('admin.dashboard', [
            'student' => $user_count,
            'shortlist' => $shortlist_count,
            'admin' => $admin_count,
            'admission' => $user_admission_count,
            'course' => $programe_count,
            'studentsPerRegion' => $studentsPerRegion,
            'studentsPerCourse' => $studentsPerCourse,
            'registrationsPerDay' => $registrationsPerDay,
            'genderDistribution' => $genderDistribution,
            'ageGroups' => $ageGroups,
            'admittedstudentsPerRegion' => $admittedstudentsPerRegion
        ]);
    }

    //Exam categories
    public function exam_category()
    {
        $data['category'] = Oex_category::get()->toArray();
        return view('admin.exam_category', $data);
    }

    //Adding new exam categories
    public function add_new_category(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validator->fails()) {
            $arr = ['status' => 'false', 'message' => $validator->errors()->all()];
        } else {
            $cat = new Oex_category();
            $cat->name = $request->name;
            $cat->status = 1;
            $cat->save();
            $arr = ['status' => 'true', 'message' => 'Success', 'reload' => url('admin/exam_category')];
        }
        echo json_encode($arr);
    }

    //Deleting the categories
    public function delete_category($id)
    {
        $cat = Oex_category::where('id', $id)->get()->first();
        $cat->delete();
        return redirect(url('admin/exam_category'));
    }

    //Editing the categories
    public function edit_category($id)
    {
        $category = Oex_category::where('id', $id)->get()->first();
        return view('admin.edit_category', ['category' => $category]);
    }

    //Editing the categories
    public function edit_new_category(Request $request)
    {
        $cat = Oex_category::where('id', $request->id)->get()->first();
        $cat->name = $request->name;
        $cat->update();
        echo json_encode(['status' => 'true', 'message' => 'updated successfully', 'reload' => url('admin/exam_category')]);
    }

    //Editing categories status
    public function category_status($id)
    {
        $cat = User::where('id', $id)->get()->first();

        if ($cat->status == 1) {
            $status = 0;
        } else {
            $status = 1;
        }

        $cat1 = User::where('id', $id)->get()->first();
        $cat1->status = $status;
        $cat1->update();
    }

    //Editing branch status
    public function branch_status($id)
    {
        $branch = Branch::where('id', $id)->get()->first();

        if ($branch->status == 1) {
            $status = 0;
        } else {
            $status = 1;
        }

        $branch1 = Branch::where('id', $id)->get()->first();
        $branch1->status = $status;
        $branch1->update();
    }

    //Editing centre status
    public function centre_status($id)
    {
        $centre = Centre::where('id', $id)->get()->first();

        if ($centre->status == 1) {
            $status = 0;
        } else {
            $status = 1;
        }

        $centre1 = Centre::where('id', $id)->get()->first();
        $centre1->status = $status;
        $centre1->update();
    }

    //Editing programme status
    public function programme_status($id)
    {
        $programme = Programme::where('id', $id)->get()->first();

        if ($programme->status == 1) {
            $status = 0;
        } else {
            $status = 1;
        }

        $programme1 = Programme::where('id', $id)->get()->first();
        $programme1->status = $status;
        $programme1->update();
    }

    //Editing course status
    public function course_status($id)
    {
        $course = Course::where('id', $id)->get()->first();

        if ($course->status == 1) {
            $status = 0;
        } else {
            $status = 1;
        }

        $course1 = Course::where('id', $id)->get()->first();
        $course1->status = $status;
        $course1->update();
    }

    //Manage exam page
    public function manage_exam()
    {
        $data['category'] = Oex_category::where('status', '1')->get()->toArray();
        $data['exams'] = Oex_exam_master::select(['oex_exam_masters.*', 'oex_categories.name as cat_name'])
            ->join('oex_categories', 'oex_exam_masters.category', '=', 'oex_categories.id')
            ->get()
            ->toArray();
        return view('admin.manage_exam', $data);
    }

    //Adding new exam page
    public function add_new_exam(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'exam_date' => 'required',
            'exam_category' => 'required',
            'passmark' => 'required',
            'exam_duration' => 'required',
        ]);

        if ($validator->fails()) {
            $arr = ['status' => 'false', 'message' => $validator->errors()->all()];
        } else {
            $exam = new Oex_exam_master();
            $exam->title = $request->title;
            $exam->exam_date = (new Carbon($request->exam_date))->setHour(23)->setMinute(59)->toDateTimeString();
            $exam->exam_duration = $request->exam_duration;
            $exam->category = $request->exam_category;
            $exam->passmark = $request->passmark;
            $exam->status = 1;
            $exam->save();

            $arr = ['status' => 'true', 'message' => 'exam added successfully', 'reload' => url('admin/manage_exam')];
        }

        echo json_encode($arr);
    }

    //editing exam status
    public function exam_status($id)
    {
        $exam = Oex_exam_master::where('id', $id)->get()->first();

        if ($exam->status == 1) {
            $status = 0;
        } else {
            $status = 1;
        }

        $exam1 = Oex_exam_master::where('id', $id)->get()->first();
        $exam1->status = $status;
        $exam1->update();
    }

    //Deleting exam status
    public function delete_exam($id)
    {
        $exam1 = Oex_exam_master::where('id', $id)->get()->first();
        $exam1->delete();
        return redirect(url('admin/manage_exam'));
    }

    //Edit Exam
    public function edit_exam($id)
    {
        $data['category'] = Oex_category::where('status', '1')->get()->toArray();
        $data['exam'] = Oex_exam_master::where('id', $id)->get()->first();

        $data['exam']['exam_date'] = (new Carbon($data['exam']['exam_date']))->toDateString();

        return view('admin.edit_exam', $data);
    }

    //Editing Exam
    public function edit_exam_sub(Request $request)
    {
        $exam = Oex_exam_master::where('id', $request->id)->get()->first();
        $exam->title = $request->title;
        $exam->exam_date = (new Carbon($request->exam_date))->setHour(23)->setMinute(59)->toDateTimeString();
        $exam->category = $request->exam_category;
        $exam->passmark = $request->passmark;
        $exam->exam_duration = $request->exam_duration;

        $exam->update();

        echo json_encode(['status' => 'true', 'message' => 'Successfully updated', 'reload' => url('admin/manage_exam')]);
    }

    //Manage students
    public function manage_students(Request $request)
    {
        $data['exam'] = Oex_exam_master::all();
        $data['exams'] = Oex_exam_master::where('status', '1')->get()->toArray();

        $data['courses'] = Course::pluck('course_name', 'id')->toArray();

        $distinctAges = User::select('age')->whereNotNull('age')->distinct()->orderBy('age')->pluck('age')->toArray();

        $data['availableAges'] = User::whereNotNull('age')->select('age')->distinct()->orderBy('age')->pluck('age')->toArray();

        if ($request->ajax()) {
            $baseQuery = user_exam::with('result')
                ->join('users', 'users.id', '=', 'user_exams.user_id')
                ->join('oex_exam_masters', 'user_exams.exam_id', '=', 'oex_exam_masters.id')
                ->leftJoin('courses', 'users.registered_course', '=', 'courses.id')
                ->leftJoin('user_admission', 'user_admission.user_id', '=', 'user_exams.user_id')
                // ->where('users.shortlist', 1)
                // ->leftJoin('courses', '')
                ->select([
                    'users.id as id',
                    'users.userId as userId',
                    'user_exams.id as exam_id',
                    'users.name',
                    'users.email',
                    'users.age',
                    'users.gender',
                    'users.created_at',
                    'courses.course_name as course_name',
                    'courses.location as course_location',
                    'oex_exam_masters.title as ex_name',
                    'oex_exam_masters.passmark',
                    'user_exams.user_id',
                    'user_exams.exam_id',
                    'user_exams.submitted',
                    'user_exams.exam_joined',
                    \DB::raw('CASE WHEN user_admission.user_id IS NOT NULL THEN "Admitted" ELSE "Not Admitted" END as admission_status')
                ]);

            // if ($request->has('ex_name')) {
            //     $baseQuery->whereIn('oex_exam_masters.title', (array) $request->ex_name);
            // }

            if ($request->has('admission_status')) {
                $admissionStatuses = (array) $request->admission_status;
                $baseQuery->where(function ($query) use ($admissionStatuses) {
                    foreach ($admissionStatuses as $status) {
                        if ($status === 'Admitted') {
                            $query->orWhereNotNull('user_admission.user_id');
                        } elseif ($status === 'Not Admitted') {
                            $query->orWhereNull('user_admission.user_id');
                        }
                    }
                });
            }

            if ($request->has('status')) {
                $statuses = (array) $request->status;
                $baseQuery->where(function ($query) use ($statuses) {
                    foreach ($statuses as $status) {
                        if ($status === 'passed') {
                            $query->orWhereHas('result', function ($q) {
                                $q->whereColumn('yes_ans', '>=', 'oex_exam_masters.passmark');
                            });
                        } elseif ($status === 'failed') {
                            $query->orWhereHas('result', function ($q) {
                                $q->whereColumn('yes_ans', '<', 'oex_exam_masters.passmark');
                            });
                        } elseif ($status === 'not_taken') {
                            $query->orWhereNull('user_exams.submitted');
                        }
                    }
                });
            }

            if ($request->has('age_range')) {
                $selectedAges = (array) $request->age_range;
                $baseQuery->where(function ($query) use ($selectedAges) {
                    foreach ($selectedAges as $age) {
                        if ($age === '0') {
                            continue;
                        }
                        $query->orWhere('users.age', $age);
                    }
                });
            }

            if ($request->has('course')) {
                $baseQuery->whereIn('users.registered_course', (array) $request->course);
            }

            // if($request->has('highest_education')){
            //     $selectedEducations = (array) $request->highest_education;
            //     $baseQuery->whereHas('formResponse', function($q) use ($selectedEducations) {
            //         $q->where(function ($subQuery) use ($selectedEducations) {
            //             foreach ($selectedEducations as $eduaction)
            //         })
            //     })
            // }

            if ($request->has('filter.search_term')) {
                $searchTerm = $request->input('filter.search_term');
                $baseQuery->where(function ($query) use ($searchTerm) {
                    $query->where('users.name', 'like', "%$searchTerm%")->orWhere('users.email', 'like', "%$searchTerm%");
                });
            }
            return DataTables::of($baseQuery)
                ->addColumn('checkbox', function ($std) {
                    return '<input type="checkbox" class="student-checkbox" value="' . $std->id . '">';
                })
                ->addColumn('age', function ($std) {
                    return $std->age ?? 'N/A';
                })
                ->addColumn('course_name', function ($std) {
                    return $std->course_name ?? 'N/A';
                })
                ->addColumn('location', function ($std) {
                    return $std->course_location ?? 'N/A';
                })

                ->addColumn('gender', function ($std) {
                    return $std->gender ?? 'N/A';
                })

                ->addColumn('date_registered', function ($std) {
                    return $std->created_at ?? 'N/A';
                })

                ->addColumn('score', function ($std) {
                    return optional($std->result)->yes_ans ?? 'N/A';
                })
                // ->addColumn('result', function ($std) {
                //     if (!$std->submitted) {
                //         return '<span class="badge badge-secondary">N/A</span>';
                //     }
                //     $yes_ans = optional($std->result)->yes_ans ?? 0;
                //     $percentage = round(($yes_ans / 30) * 100);
                //     $class = $yes_ans >= $std->passmark ? 'success' : 'danger';
                //     return '<span class="badge badge-' . $class . '">' . $percentage . '%</span>';
                // })
                ->addColumn('status', function ($std) {
                    if (!$std->submitted) {
                        return '<span class="badge badge-secondary">Not Taken</span>';
                    }
                    $passed = optional($std->result)->yes_ans >= $std->passmark;
                    return $passed ? '<span class="badge badge-success">PASS</span>' : '<span class="badge badge-danger">FAIL</span>';
                })
                ->addColumn('actions', function ($std) {
                    $buttons = [];

                    $viewButton = '<a href="' . url('admin/admin_view_result/' . $std->user_id) . '" class="btn btn-success">' . 'View <i class="fas fa-poll"></i>' . '</a>';

                    $dropdownToggle = '<button type="button" class="btn btn-success btn-sm dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-expanded="false">' . '<span class="sr-only">Toggle Dropdown</span>' . '</button>';

                    $dropdownMenu = '<div class="dropdown-menu">';
                    $dropdownMenu .= '<a class="dropdown-item" href="' . url('admin/delete_students/' . $std->id) . '">Delete <i class="fas fa-trash-alt"></i></a>';
                    $dropdownMenu .= '<a class="dropdown-item" href="' . route('admin.reset-exam', [$std->exam_id, $std->user_id]) . '">Reset Result <i class="fas fa-redo"></i></a>';
                    if (Auth::user()->hasRole('super-admin')) {
                        $dropdownMenu .= '<a class="dropdown-item" href="' . route('admin.login_as_student', $std->user_id) . '">Login As <i class="fas fa-user"></i></a>';
                    }
                    $dropdownMenu .= '</div>';

                    if ($std->exam_joined) {
                        return '<div class="btn-group">' . $viewButton . $dropdownToggle . $dropdownMenu . '</div>';
                    } else {
                        $buttons[] = '<a href="' . url('admin/delete_students/' . $std->id) . '" class="btn btn-danger btn-sm">Delete <i class="fas fa-trash-alt"></i></a>';
                        $buttons[] = '<a href="' . route('admin.reset-exam', [$std->exam_id, $std->user_id]) . '" class="btn btn-info btn-sm">Reset Result <i class="fas fa-redo"></i></a>';
                        return '<div class="btn-group">' . implode('', $buttons) . '</div>';
                    }
                })
                ->with(['all_filtered_ids' => $baseQuery->pluck('user_exams.id')->toArray()])
                ->rawColumns(['checkbox', 'result', 'status', 'actions'])
                ->toJson();
        }

        $data['mailable'] = MailerHelper::getMailableClasses();

        // return view('mailables.index', ['mailables' => $mailables]);

        return view('admin.manage_students', $data);
    }


    public function login_as_student(Request $request, $user_id)
    {
        $user = User::find($user_id);
        if ($user) {
            Auth::guard('web')->login($user);

            return redirect()->route('student.dashboard');
        } else {
            return redirect()->back()->with('error', 'User not found.');
        }

    }



    public function add_new_students(Request $request)
    {
        $data = $request->input('students') !== null ? $request->input('students') : [$request->all()];

        AddNewStudentsJob::dispatch($data);

        echo json_encode(['status' => 'true', 'message' => 'Successfully updated', 'reload' => url('admin/manage_students')]);
    }

    //Editing student status
    public function student_status($id)
    {
        $std = user_exam::where('id', $id)->get()->first();

        if ($std->std_status == 1) {
            $status = 0;
        } else {
            $status = 1;
        }

        $std1 = user_exam::where('id', $id)->get()->first();
        $std1->std_status = $status;
        $std1->update();
    }

    //Deleting students
    public function delete_students($id)
    {
        $std = user_exam::where('id', $id)->get()->first();
        $std?->delete();
        return redirect('admin/manage_students');
    }

    //Editing students
    public function edit_students_final(Request $request)
    {
        $std = User::where('id', $request->id)->get()->first();
        $std->name = $request->name;
        $std->email = $request->email;
        $std->mobile_no = $request->mobile_no;

        if ($std->exam != $request->exam) {
            user_exam::create([
                'user_id' => $std->id,
                'exam_id' => $request->exam,
                'std_status' => 1,
                'exam_joined' => 0,
            ]);
        }

        $std->exam = $request->exam;

        if ($request->password != '') {
            $std->password = $request->password;
        }

        $std->update();
        echo json_encode(['status' => 'true', 'message' => 'Successfully updated', 'reload' => url('admin/manage_students')]);
    }


    //Shortlisted student page
    public function shortlisted_students(Request $request)
    {
        $data['exam'] = Oex_exam_master::all();
        $data['exams'] = Oex_exam_master::where('status', '1')->get()->toArray();
        $data['courses'] = Course::pluck('course_name', 'id')->toArray();
        $data['sessions'] = CourseSession::all();

        if ($request->ajax()) {
            $baseQuery = user_exam::with('result')
                ->join('users', 'users.id', '=', 'user_exams.user_id') // Join with users
                ->leftJoin('user_admission', 'user_admission.user_id', '=', 'users.userId') // Join with user_admission
                ->leftJoin('courses', 'user_admission.course_id', '=', 'courses.id') // Join with courses
                ->leftJoin('course_sessions', 'user_admission.session', '=', 'course_sessions.id') // Join with course_sessions
                ->where('users.shortlist', 1) // This filters users with shortlist = 1
                ->select([
                    'user_exams.id as exam_id',
                    'users.id',
                    'users.name',
                    'users.email',
                    'users.gender',
                    'users.age',
                    'users.shortlist',
                    'users.created_at',
                    'users.userId as userId',
                    'user_exams.user_id',
                    'user_exams.exam_id',
                    'user_exams.exam_joined',
                    'user_exams.submitted',
                    'user_admission.id as admitted',
                    \DB::raw('CASE WHEN user_admission.id IS NOT NULL THEN "Admitted" ELSE "Not Admitted" END as admission_status'),
                    \DB::raw('CASE WHEN user_admission.id IS NOT NULL THEN courses.course_name ELSE NULL END as course_name'),
                    \DB::raw('CASE WHEN user_admission.id IS NOT NULL THEN course_sessions.name ELSE NULL END as session_name'),
                    'user_admission.session as session_id',
                    'courses.id as course_id',
                ]);

            // Apply additional filters
            if ($request->has('admission_status')) {
                $statuses = (array) $request->admission_status;
                $baseQuery->where(function ($q) use ($statuses) {
                    foreach ($statuses as $status) {
                        if ($status === 'Admitted') {
                            $q->orWhereNotNull('user_admission.id');
                        } elseif ($status === 'Not Admitted') {
                            $q->orWhereNull('user_admission.id');
                        }
                    }
                });
            }

            // Filter by course
            if ($request->has('course')) {
                $baseQuery->whereIn('users.registered_course', (array) $request->course);
            }

            // Search
            if ($request->has('filter.search_term')) {
                $term = $request->input('filter.search_term');
                $baseQuery->where(function ($query) use ($term) {
                    $query->where('users.name', 'like', "%$term%")
                        ->orWhere('users.email', 'like', "%$term%");
                });
            }

            return DataTables::of($baseQuery)
                ->addColumn('checkbox', fn($std) => '<input type="checkbox" class="student-checkbox" value="' . $std->id . '">')
                ->addColumn('session_name', fn($std) => $std->session_name ?? 'N/A')
                ->addColumn('course_name', fn($std) => $std->course_name ?? 'N/A')
                ->addColumn('admission_status', function ($std) {
                    return $std->admission_status === 'Admitted'
                        ? '<span class="badge badge-success">Admitted</span>'
                        : '<span class="badge badge-secondary">Not Admitted</span>';
                })
                ->addColumn('actions', function ($std) {
                    $buttons = ['<a href="' . url('admin/delete_students/' . $std->id) . '" class="btn btn-danger btn-sm">Delete</a>'];
                    if ($std->exam_joined) {
                        $buttons[] = '<a href="' . url('admin/admin_view_result/' . $std->user_id) . '" class="btn btn-success btn-sm">View Result</a>';
                    }
                    $buttons[] = '<a href="' . route('admin.reset-exam', [$std->exam_id, $std->user_id]) . '" class="btn btn-info btn-sm">Reset Result</a>';
                    return implode(' ', $buttons);
                })
                ->with(['all_filtered_ids' => $baseQuery->pluck('userId')->toArray()])
                ->rawColumns(['checkbox', 'session_name', 'course_name', 'admission_status', 'actions'])
                ->toJson();
        }

        $data['mailable'] = MailerHelper::getMailableClasses();
        return view('admin.manage_shortlist_students', $data);
    }



    //Registered student page
    public function registered_students()
    {
        $data['users'] = User::select('users.*', 'user_admission.id as admitted', 'courses.course_name', 'course_sessions.name as session_name', 'user_admission.session as session_id', 'courses.id as course_id')->leftJoin('user_admission', 'users.userId', '=', 'user_admission.user_id')->leftJoin('courses', 'user_admission.course_id', '=', 'courses.id')->leftJoin('course_sessions', 'user_admission.session', '=', 'course_sessions.id')->paginate(15);
        $courses = Course::all();
        $sessions = CourseSession::all();
        $data['courses'] = $courses;
        $data['sessions'] = $sessions;

        return view('admin.registered_students', $data);
    }

    //Deleting students egistered
    public function delete_registered_students($id)
    {
        $std = User::where('id', $id)->get()->first();
        $std->delete();
        $std_form_response = FormResponse::where('id', $std->form_response_id)->get()->first();
        $std_form_response->delete();
        return redirect('admin/registered_students');
    }

    //addning questions
    public function add_questions($id)
    {
        $data['questions'] = Oex_question_master::where('exam_id', $id)->get()->toArray();
        return view('admin.add_questions', $data);
    }

    //adding new questions
    public function add_new_question(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required',
            'option_1' => 'required',
            'option_2' => 'required',
            'option_3' => 'required',
            'option_4' => 'required',
            'ans' => 'required',
        ]);

        if ($validator->fails()) {
            $arr = ['status' => 'flase', 'message' => $validator->errors()->all()];
        } else {
            $q = new Oex_question_master();
            $q->exam_id = $request->exam_id;
            $q->questions = $request->question;

            if ($request->ans == 'option_1') {
                $q->ans = $request->option_1;
            } elseif ($request->ans == 'option_2') {
                $q->ans = $request->option_2;
            } elseif ($request->ans == 'option_3') {
                $q->ans = $request->option_3;
            } else {
                $q->ans = $request->option_4;
            }

            $q->status = 1;
            $q->options = json_encode(['option1' => $request->option_1, 'option2' => $request->option_2, 'option3' => $request->option_3, 'option4' => $request->option_4]);

            $q->save();

            $arr = ['status' => 'true', 'message' => 'successfully added', 'reload' => url('admin/add_questions/' . $request->exam_id)];
        }

        echo json_encode($arr);
    }

    //Edit question status
    public function question_status($id)
    {
        $p = Oex_question_master::where('id', $id)->get()->first();

        if ($p->status == 1) {
            $status = 0;
        } else {
            $status = 1;
        }

        $p1 = Oex_question_master::where('id', $id)->get()->first();
        $p1->status = $status;
        $p1->update();
    }

    //Delete questions
    public function delete_question($id)
    {
        $q = Oex_question_master::where('id', $id)->get()->first();
        $exam_id = $q->exam_id;
        $q->delete();

        return redirect(url('admin/add_questions/' . $exam_id));
    }

    //update questions
    public function update_question($id)
    {
        $data['q'] = Oex_question_master::where('id', $id)->get()->toArray();

        return view('admin.update_question', $data);
    }

    //Edit question
    public function edit_question_inner(Request $request)
    {
        $q = Oex_question_master::where('id', $request->id)->get()->first();

        $q->questions = $request->question;

        if ($request->ans == 'option_1') {
            $q->ans = $request->option_1;
        } elseif ($request->ans == 'option_2') {
            $q->ans = $request->option_2;
        } elseif ($request->ans == 'option_3') {
            $q->ans = $request->option_3;
        } else {
            $q->ans = $request->option_4;
        }

        $q->options = json_encode(['option1' => $request->option_1, 'option2' => $request->option_2, 'option3' => $request->option_3, 'option4' => $request->option_4]);

        $q->update();

        echo json_encode(['status' => 'true', 'message' => 'successfully updated', 'reload' => url('admin/add_questions/' . $q->exam_id)]);
    }

    public function admin_view_result($id)
    {
        $std_exam = user_exam::where('user_id', $id)->first();

        $data['result_info'] = Oex_result::where('exam_id', $std_exam->exam_id)->where('user_id', $id)->first();

        $data['student_info'] = User::where('id', $id)->first();

        $data['exam_info'] = Oex_exam_master::where('id', $std_exam->exam_id)->first();

        return view('admin.admin_view_result', $data);
    }

    public function generate_qrcode_page()
    {
        $courses = Course::distinct('course_name')->get()->all();

        return view('admin.qr-generator', [
            // "locations" => $locations,
            'courses' => $courses,
        ]);
    }

    public function scan_qrcode_page()
    {
        // $courses = auth('admin')->user()->assignedCourses()->get();
        $courses = Course::myAssignedCourses()->get()->groupBy('location');

        return view('admin.qr-scanner', [
            // "locations" => $locations,
            'groupedCourses' => $courses,
        ]);
    }

    public function verifyDetails(Request $request)
    {
        // $courses = Course::all();
        $courses = Course::myAssignedCourses()->get()->groupBy('location');


        $students = collect();
        $selectedCourse = null;

        if ($request->has('course_id')) {
            $selectedCourse = Course::find($request->input('course_id'));

            if ($selectedCourse) {
                $students = UserAdmission::where('course_id', $selectedCourse->id)->join('users', 'user_admission.user_id', '=', 'users.userId')->select('users.*')->get();
            }
        }

        return view('admin.verify_student_details', compact('courses', 'students', 'selectedCourse'));
    }

    public function verifyStudent($id)
    {
        $student = User::find($id);

        // match with ghana card format
        $correctFormat = preg_match('/GHA-[0-9]{9}-[0-9]{1}$/', $student->ghcard);

        $courses = Course::myAssignedCourses()->pluck('id')->values()->all();

        $allowed = in_array($student->admission->course_id, $courses);

        if (!$allowed) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot verify students of this course'
            ]);
        }


        if (($student && $correctFormat) || ($student && $student->ghcard && $student->card_type !== 'ghcard')) {
            $adminId = Auth::guard('admin')->id();
            $student->verification_date = now();
            $student->verified_by = $adminId;
            $student->save();
            $student->verified_by_name = Admin::find($student->verified_by)->name;

            // UpdateSheetWithGhanaCardDetails::dispatch($student);

            return response()->json([
                'success' => true,
                'message' => 'Verification successsful',
                'student' => $student,
            ]);
        }
        return response()->json([
            'success' => false,
            'message' => 'Unable to verify. Card Number format is wrong',
        ]);
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




    public function verification_page(Request $request)
    {
        // $allCourses = auth('admin')->user()->assignedCourses()->get();
        $allCourses = Course::myAssignedCourses()->get();
        $students = [];

        $selectedCourse = $request->input('course_id');

        if (isset($selectedCourse)) {
            $students = UserAdmission::select('users.*', 'user_admission.created_at as admission_created', 'user_admission.updated_at as admission_updated', DB::raw('(select admins.name from admins where admins.id = users.verified_by) as verified_by_name'))
                ->join('users', 'users.userId', 'user_admission.user_id')

                ->where('course_id', $selectedCourse)
                ->get();
            $selectedCourse = Course::find($selectedCourse);
        }


        return view('admin.verification', [
            'courses' => $allCourses,
            'students' => $students,
            'selectedCourse' => $selectedCourse,
            'groupedCourses' => $allCourses->groupBy('location'),
        ]);
    }

    public function viewAttendanceByDate(Request $request)
    {
        // $courses = auth('admin')->user()->assignedCourses()->get();
        $courses = Course::myAssignedCourses()->get();
        $sessions = CourseSession::whereIn('course_id', $courses->pluck('id')->all())
            ->select('id', 'session', 'name', 'course_id')->get();
        $attendance = collect();
        $selectedCourse = null;
        $selectedDate = null;

        if ($request->has('course_id') && $request->has('date')) {
            if (!in_array($request->course_id, $courses->pluck('id')->all())) {
                return back()->with([
                    'key' => 'error',
                    'flash' => 'You do not have permission to view this course'
                ]);
            }
            $selectedCourse = Course::find($request->input('course_id'));
            $selectedDate = $request->input('date');
            $selectedSessions = $request->input('session_ids') != "" ? explode(',', trim($request->input('session_ids'))) : [];


            if ($selectedCourse && $selectedDate) {
                $attendance = Attendance::select('attendances.*', 'users.name', 'users.email', 'course_sessions.session')
                    ->join('users', 'users.userId', '=', 'attendances.user_id')
                    ->join('user_admission', 'user_admission.user_id', '=', 'users.userId')
                    ->join('course_sessions', 'course_sessions.id', '=', 'user_admission.session')
                    ->where('attendances.course_id', $selectedCourse->id)
                    ->whereDate('attendances.date', '=', $selectedDate);
                if ($selectedSessions && count($selectedSessions) > 0) {
                    $attendance->whereIn('course_sessions.id', $selectedSessions);
                }
                $attendance = $attendance->get();
            }
        }

        return view('admin.view_attendance', [
            'courses' => $courses,
            'attendance' => $attendance,
            'selectedCourse' => $selectedCourse,
            'selectedDate' => $selectedDate,
            'sessions' => $sessions,
            'groupedCourses' => $courses->groupBy('location'),
            'selectedSessions' => $selectedSessions ?? []
        ]);
    }

    public function admit_student(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|nullable|exists:courses,id',
            'session_id' => 'sometimes|nullable|exists:course_sessions,id',
            'user_id' => 'sometimes|nullable|required_if:user_ids,null|exists:users,userId',
            'change' => 'sometimes',
            'user_ids' => 'sometimes|nullable|required_if:user_id,null|array',
            'user_ids.*' => 'exists:users,userId',
        ]);

        $course = Course::find($validated['course_id']);
        $session = CourseSession::find($validated['session_id'] ?? '');
        $change = $validated['change'] == 'true';

        if ($session && $session->course_id != $course->id) {
            return redirect()->back()->with([
                'flash' => 'Session not valid for selected course',
                'key' => 'error',
            ]);
        }
        $message = 'Student(s) admitted successfully';

        if ($validated['user_id'] ?? false) {
            $user_id = $validated['user_id'];
            $user = User::where('userId', $user_id)->first();
            CreateStudentAdmissionJob::dispatch($user, $course, $session);
            $oldAdmission = UserAdmission::where('user_id', $user_id)->first();
            if ($oldAdmission && $change) {
                $message = 'Student admission changed successfully';
            }
        } else if (count($validated['user_ids'] ?? []) > 0) {
            $user_ids = $validated['user_ids'];
            foreach ($user_ids as $user_id) {
                $user = User::where('userId', $user_id)->first();
                CreateStudentAdmissionJob::dispatch($user, $course, $session);
            }
        }
        return redirect()->back()->with([
            'flash' => $message,
            'key' => 'success',
        ]);
    }


    public function reset_verify($userId)
    {
        $user = User::findOrFail($userId);

        $user->details_updated_at = null;
        $user->verified_by = null;
        $user->verification_date = null;
        $user->save();

        return redirect()
            ->back()
            ->with([
                'flash' => 'Student reset successfully',
                'key' => 'success',
            ]);
    }

    public function getReportView()
    {
        $courses = Course::all();

        // find students that have attendance for the selected dates

        $data = [
            'courses' => $courses,
            'attendanceData' => [],
            'startDate' => now()->toDateString(),
            'endDate' => now()->toDateString(),
            'dates_array' => [],
            'report_type' => null,
            'dates' => '',
            'selectedCourse' => [],
            'selectedDailyOption' => 'no',
            'virtualQuery' => false,
            'virtual_week' => [],
        ];

        return view('admin.reports', $data);
    }

    public function generateReport(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|in:student_summary,course_summary',
            'dates' => 'required',
            'course_id' => 'sometimes|array',
            'daily' => 'sometimes|in:yes,no',
            'course_id.*' => 'numeric|min:0',
            'virtual_week' => 'sometimes|array',
            'virtual_week.*' => 'numeric|min:1|max:54',
        ]);

        $startDate = Carbon::parse(explode(' - ', $request->dates)[0]);
        $endDate = Carbon::parse(explode(' - ', $request->dates)[1]);

        $dates = $this->getWeekdays($startDate, $endDate);

        $courses = Course::all();
        $selectedCourse = null;
        $studentAttendanceData = collect();
        $attendanceData = collect();

        $dailyQuery = isset($validated['daily']) && $validated['daily'] == 'yes';

        if ($request->get('report_type') == 'course_summary') {
            // find students that have attendance for the selected dates
            $attendanceData = DB::table('vDailyCourseAttendance', 'v1');
            if ($dailyQuery) {
                $attendanceData = $attendanceData->whereRaw('DATE(attendance_date) BETWEEN ? AND ?', [$startDate, $endDate]);
            }

            $attendanceData = $attendanceData
                ->whereRaw('DATE(attendance_date) BETWEEN ? AND ?', [$startDate, $endDate])
                ->select('v1.*')
                ->selectRaw('(SELECT AVG(v2.total) from `vDailyCourseAttendance` v2 where v2.course_id = v1.course_id AND DATE(attendance_date) BETWEEN ? AND ? group by v1.course_id ) as average', [$startDate, $endDate])
                ->selectRaw('(SELECT SUM(v2.total) from `vDailyCourseAttendance` v2 where v2.course_id = v1.course_id AND DATE(attendance_date) BETWEEN ? AND ? group by v1.course_id ) as attendance_total', [$startDate, $endDate])
                ->orderBy('course_id', 'desc')
                ->orderBy('attendance_date')
                ->get()
                ->groupBy(['course_name', 'attendance_date']);
        }

        if ($request->get('report_type') == 'student_summary') {
            $courseId = $validated['course_id'] ?? [0];
            $whereCourseClause = $courseId[0] == 0 ? '' : ' WHERE c.id in ( ' . implode(',', collect($courseId)->flatten()->all()) . ')';
            // use total attendance when start to date and no daily
            // if ($s)
            $virtualQuery = isset($validated['virtual_week']) && count($validated['virtual_week']) > 0;

            $whereVirtualClause = $virtualQuery ? ' AND WEEK(a.date, 3) IN (' . implode(',', $validated['virtual_week']) . ') ' : '';
            $optimizeQuery =
                "select _ta.total as attendance_total, _ta.user_id,
                u.name as user_name, u.email as email, u.gender as user_gender, u.contact as user_contact, u.network_type as user_network_type, c.course_name, c.location as course_location, c.id " .
                ($dailyQuery ? ', _da.date as attendance_date' : '') .
                ($virtualQuery ? ', _va.t as virtual_attendance, (_ta.total - _va.t) as in_person' : '') .
                " from
                (select count(distinct `a`.`date`) AS `total`,max(`a`.`user_id`) AS `user_id`
                from `attendances` `a`
                where DATE(a.date) between ? AND ?
                group by `a`.`user_id`) as _ta " .
                ($virtualQuery
                    ? "inner join
                (select COUNT(*) as t, MAX(a.user_id) AS user_id FROM `attendances` `a`
                where DATE(a.date) between ? AND ? $whereVirtualClause
                group by `a`.`user_id` ) as _va
                ON _ta.user_id  = _va.user_id "
                    : '') .
                ($dailyQuery
                    ? "inner join
                (select distinct date_format(`a`.`date`,'%Y-%m-%d') AS `date`,
                `a`.`user_id` from `attendances` `a`
                where DATE(a.date) between ? AND ? ) as _da
                ON _ta.user_id  = _da.user_id "
                    : '') .
                "
                left join users u on u.userId = _ta.user_id
                left join user_admission ua on ua.user_id = _ta.user_id
                inner join courses c on c.id = ua.course_id
                $whereCourseClause order by c.course_name, u.name";
            $dateParams = [$startDate, $endDate];
            $params = $dateParams;
            $groupBy = ['user_id', 'attendance_date'];

            if ($dailyQuery) {
                $params = array_merge($params, $dateParams);
            }

            if ($virtualQuery) {
                $params = array_merge($params, $dateParams);
            }

            // if ($courseId[0] !== 'all') {
            //     // $params[] = "(" . implode(',', (collect($courseId)->flatten()->all())) . ") ";
            // }
            // DB::prepareBindings($params);
            // dd($optimizeQuery);
            // DB::bindValues($optimizeQuery, $params);

            $studentAttendanceData = collect(DB::select($optimizeQuery, $params))->groupBy($groupBy);
            // dd($studentAttendanceData);

            $selectedCourse = $courseId[0] == '0' || count($courseId) > 1 ? '0' : Course::find($courseId[0]);
        }

        $data = [
            'courses' => $courses,
            'attendanceData' => $attendanceData,
            'studentAttendanceData' => $studentAttendanceData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dates_array' => $dates,
            'report_type' => $request->get('report_type'),
            'dates' => $request->get('dates'),
            'selectedCourse' => $selectedCourse ?? '0',
            'selectedDailyOption' => $request->get('daily'),
            'virtualQuery' => $virtualQuery,
            'virtual_week' => $validated['virtual_week'] ?? [],
        ];
        // dd($data);
        return view('admin.reports', $data);
    }

    public function sendBulkEmail(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required',
            'message' => 'sometimes',
            'template' => 'required_if:message,null',
            'student_ids' => 'required_if:list,null|nullable|array',
            'student_ids.*' => 'exists:users,id',
            'list' => 'required_if:student_ids,null|nullable|string'
        ], [], [
            'student_ids.*' => 'student'
        ]);

        // if no list_name or students_id
        if (empty($validated['list']) && empty($validated['student_ids'])) {
            return redirect()->back()->with([
                'flash' => 'No students/ list selected.',
                'key' => 'error',
            ]);
        }

        SendBulkEmailJob::dispatch($validated);

        return response()->json([
            'flash' => 'SMS sending initiated successfully!',
            'key' => 'success',
        ]);
    }

    public function fetch_sms_template()
    {
        // Fetch the templates
        $templates = SmsTemplate::select('id', 'name', 'content')->get();

        return response()->json($templates);
    }



    public function sendBulkSMS(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'student_ids' => 'sometimes|nullable|array',
            'student_ids.*' => 'exists:users,id',
            'list' => 'required_if:student_ids,null|nullable|string'
        ], [], [
            'student_ids.*' => 'student'
        ]);

        if (empty($validated['list']) && empty($validated['student_ids'])) {
            return redirect()->back()->with([
                'flash' => 'No students/ list selected.',
                'key' => 'error',
            ]);
        }

        SendBulkSMSJob::dispatch($validated);


        return response()->json([
            'flash' => 'SMS sending initiated successfully!',
            'key' => 'success',
        ]);
    }






    public function saveShortlistedStudents(Request $request)
    {
        $request->validate([
            'emails' => 'sometimes|array',
            'emails.*' => 'email',
            'student_ids' => 'sometimes|array',
            'student_ids.*' => 'numeric',
            'phone_numbers' => 'sometimes|array',
            // 'phone_numbers.*' => 'phone'
        ], [], [
            'emails.*' => 'email address',
            'student_ids.*' => 'student'
        ]);
        if (empty($request->input('emails')) && empty($request->input('student_ids')) && empty($request->input('phone_numbers'))) {
            return response()->json([
                'message' => 'Email(s), Student ID(s), or PhoneNumber(s) are required.',
            ], 400);
        }

        $data = $request->input('emails') ?? $request->input('student_ids') ?? $request->input('phone_numbers');
        $columnName  = $request->has('emails')
            ? 'email'
            : ($request->has('phone_numbers') ? 'mobile_no' : 'id');;

        $usersToUpdate = User::whereIn($columnName, (array) $data)
            ->where(function ($query) {
                $query->whereNull('shortlist')
                    ->orWhere('shortlist', '!=', 1);
            })
            ->get();

        if ($usersToUpdate->isEmpty()) {
            return response()->json([
                'message' => 'No users found to update or all are already shortlisted.',
            ], 404);
        }

        $updatedCount = User::whereIn('id', $usersToUpdate->pluck('id'))
            ->update(['shortlist' => 1]);

        return response()->json([
            'message' => "$updatedCount user(s) successfully shortlisted.",
        ]);
    }


    public function getSettingsPage()
    {
        return view('admin.appsettings.index');
    }

    public function saveSettings(Request $request)
    {
        // $validated =
    }
}
