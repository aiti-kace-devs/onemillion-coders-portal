<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UserRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Http\Controllers\Traits\BulkStudentActionsTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\ShortlistActionsTrait;
use App\Http\Controllers\Traits\ShortlistRowActionsTrait;
use App\Jobs\CreateStudentAdmissionJob;
use App\Models\CourseSession;
use App\Models\UserAdmission;
use App\Models\Course;
use App\Models\User;
use App\Helpers\UserFieldHelpers;
use App\Helpers\WidgetHelper;
use App\Helpers\FilterHelper;
/**
 * Class UserCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserCrudController extends CrudController
{
    use BulkStudentActionsTrait;
    use ShortlistActionsTrait;
    use ShortlistRowActionsTrait;
    use \App\SearchableCRUD;
    use UserFieldHelpers;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\Pro\Http\Controllers\Operations\CustomViewOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user');
        CRUD::setEntityNameStrings('student', 'students');

        $this->setSearchableColumns(['name', 'email', 'mobile_no']);
        $this->setSearchResultAttributes(['id', 'name', 'email', 'mobile_no']);

        $this->crud->operation('list', function () {
            WidgetHelper::userStatisticsWidget();
        });

        // Add permission checks
        $this->crud->operation(['list', 'show'], function () {
            $this->crud->addClause('where', function ($query) {
                if (!backpack_user()->can('student.read.all')) {
                    // Add any specific filtering logic here if needed
                }
            });
        });
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        // Check permissions
        if (!backpack_user()->can('student.read.all')) {
            abort(403, 'Unauthorized action.');
        }

        $this->crud->setModel(User::class);
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/user');
        $this->crud->setEntityNameStrings('user', 'users');

        $this->crud->query->select(['id','name', 'gender', 'age', 'email', 'mobile_no', 'ghcard']);

        $this->crud->addColumn(['name' => 'name', 'type' => 'text', 'label' => 'Full Name']);
        $this->crud->addColumn(['name' => 'email', 'type' => 'text', 'label' => 'Email']);
        $this->crud->addColumn(['name' => 'gender', 'type' => 'text', 'label' => 'Gender']);
        $this->crud->addColumn(['name' => 'age', 'type' => 'text', 'label' => 'Age']);
        $this->crud->addColumn(['name' => 'mobile_no', 'type' => 'text', 'label' => 'Mobile No']);
        // $this->crud->addColumn(['name' => 'ghcard', 'type' => 'text', 'label' => 'Ghana Card Number']);
        $this->addConfirmedAdmissionColumn();
        FilterHelper::addBooleanColumn('shortlist', 'Shortlist');
        
        // $this->setupStudentColumns();
        $this->addStudentBatchFilter('admission', 'Student Batch');
        $this->courseFilter('registered_course');
        $this->addConfirmedAdmissionFilter();
        FilterHelper::addBooleanFilter('shortlist', 'Shortlist');
        FilterHelper::addAgeRangeFilter();
        FilterHelper::addGenderFilter();
        $this->addAdmissionLocationFilter();
        $this->addAdmittedAtFilter();
        // Disable responsive table
        // CRUD::disableResponsiveTable();

        
        // Enable bulk operations
        CRUD::enableBulkActions();

        // Add export options
        CRUD::enableExportButtons();

        // Add custom views
        $this->runCustomViews([
            'setupStudentsWithAdmissionView' => 'Students with Admission',
            'setupStudentsWithoutExamResultsView' => 'Students without Exam Results',
            'setupStudentsYetToAcceptAdmissionView' => 'Students Yet to Accept Admission',
            'setupStudentsWithExamResultsView' => 'Students with Exam Results',
            'setupShortlistedStudentsView' => 'Shortlisted Students',
        ]);

        // Add bulk action buttons
        CRUD::addButtonFromView('top', 'bulk_actions_dropdown', 'bulk_actions_dropdown', 'beginning');
        CRUD::addButton('top', 'assign_batch_bulk', 'view', 'admin.bulk.assign_batch', 'end');
    }

        protected function setupShowOperation()
    {
        $this->setupShowStudentColumns();
    }
    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        // Check permissions
        if (!backpack_user()->can('student.create')) {
            abort(403, 'Unauthorized action.');
        }

        CRUD::setValidation(UserRequest::class);
        CRUD::setFromDb(); // set fields from db columns.

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        // Check permissions
        if (!backpack_user()->can('student.update')) {
            abort(403, 'Unauthorized action.');
        }

        $this->setupCreateOperation();
    }

    /**
     * Define what happens when the Delete operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-delete
     * @return void
     */
    protected function setupDeleteOperation()
    {
        // Check permissions
        if (!backpack_user()->can('student.delete')) {
            abort(403, 'Unauthorized action.');
        }
    }


    public function assignBatch(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'batch_id' => 'required|integer|exists:admission_batches,id',
        ]);

        $updated = 0;
        $studentIds = $request->student_ids;

        $userIds = User::whereIn('id', $studentIds)->pluck('userId')->toArray();

        foreach (array_chunk($userIds, 100) as $chunk) {
            $affected = UserAdmission::whereIn('user_id', $chunk)
                ->update(['batch_id' => $request->batch_id]);
            $updated += $affected;
        }

        if ($updated === 0) {
            return response()->json(['message' => 'No admissions updated.'], 400);
        }

        return response()->json(['message' => 'Batch assignment successful']);
    }
    




    /**
     * Custom view for students with admission
     */
    public function setupStudentsWithAdmissionView()
    {
        // Enable bulk operations for this view
        CRUD::enableBulkActions();

        // Filter students who have admission records with session
        CRUD::setQuery(\App\Models\User::whereHas('admissions', function ($query) {
            $query->whereNotNull('session');
        }));

        // Add a custom column to show admission status
        CRUD::addColumn([
            'name' => 'admission_status',
            'label' => 'Admission Status',
            'type' => 'text',
            'value' => function ($entry) {
                $admission = $entry->admissions()->whereNotNull('session')->first();
                return $admission ? 'Admitted' : 'Not Admitted';
            }
        ]);

        // Add admission date column
        CRUD::addColumn([
            'name' => 'admission_date',
            'label' => 'Admission Date',
            'type' => 'date',
            'value' => function ($entry) {
                $admission = $entry->admissions()->whereNotNull('session')->first();
                return $admission ? $admission->created_at : null;
            }
        ]);

        // Add course column
        CRUD::addColumn([
            'name' => 'admitted_course',
            'label' => 'Admitted Course',
            'type' => 'text',
            'value' => function ($entry) {
                $admission = $entry->admissions()->whereNotNull('session')->first();
                if ($admission && $admission->course) {
                    return $admission->course->course_name ?? 'N/A';
                }
                return 'N/A';
            }
        ]);

        // Add exam score column
        CRUD::addColumn([
            'name' => 'exam_score',
            'label' => 'Exam Score',
            'type' => 'text',
            'value' => function ($entry) {
                $latestResult = $entry->examResults()->latest()->first();
                if ($latestResult) {
                    return $latestResult->result . '%';
                }
                return 'N/A';
            }
        ]);
    }

    /**
     * Custom view for students without exam results
     */
    public function setupStudentsWithoutExamResultsView()
    {
        // Enable bulk operations for this view
        CRUD::enableBulkActions();

        // Filter students who don't have exam results
        CRUD::setQuery(\App\Models\User::whereDoesntHave('examResults'));

        // Add a custom column to show exam status
        CRUD::addColumn([
            'name' => 'exam_status',
            'label' => 'Exam Status',
            'type' => 'text',
            'value' => function ($entry) {
                return 'No Exam Results';
            }
        ]);

        // Add a column to show if student has taken exams
        CRUD::addColumn([
            'name' => 'exams_taken',
            'label' => 'Exams Taken',
            'type' => 'text',
            'value' => function ($entry) {
                $examsTaken = $entry->userExams()->count();
                return $examsTaken > 0 ? $examsTaken . ' exam(s)' : 'No exams taken';
            }
        ]);

        // Add a column to show if student has submitted exams
        CRUD::addColumn([
            'name' => 'submitted_exams',
            'label' => 'Submitted Exams',
            'type' => 'text',
            'value' => function ($entry) {
                $submittedExams = $entry->userExams()->whereNotNull('submitted')->count();
                return $submittedExams > 0 ? $submittedExams . ' submitted' : 'No submitted exams';
            }
        ]);
    }

    /**
     * Custom view for students yet to accept admission (session_id is null)
     */
    public function setupStudentsYetToAcceptAdmissionView()
    {
        // Enable bulk operations for this view
        CRUD::enableBulkActions();

        // Filter students who have admission records but session_id is null
        CRUD::setQuery(\App\Models\User::whereHas('admissions', function ($query) {
            $query->whereNull('session');
        }));

        // Add a custom column to show admission status
        CRUD::addColumn([
            'name' => 'admission_status',
            'label' => 'Admission Status',
            'type' => 'text',
            'value' => function ($entry) {
                $admission = $entry->admissions()->whereNull('session')->first();
                return $admission ? 'Pending Acceptance' : 'No Admission';
            }
        ]);

        // Add admission date column
        CRUD::addColumn([
            'name' => 'admission_date',
            'label' => 'Admission Date',
            'type' => 'date',
            'value' => function ($entry) {
                $admission = $entry->admissions()->whereNull('session')->first();
                return $admission ? $admission->created_at : null;
            }
        ]);

        // Add course column
        CRUD::addColumn([
            'name' => 'offered_course',
            'label' => 'Offered Course',
            'type' => 'text',
            'value' => function ($entry) {
                $admission = $entry->admissions()->whereNull('session')->first();
                if ($admission && $admission->course) {
                    return $admission->course->course_name ?? 'N/A';
                }
                return 'N/A';
            }
        ]);

        // Add email sent status
        CRUD::addColumn([
            'name' => 'email_sent',
            'label' => 'Email Sent',
            'type' => 'text',
            'value' => function ($entry) {
                $admission = $entry->admissions()->whereNull('session')->first();
                return $admission && $admission->email_sent ? 'Yes' : 'No';
            }
        ]);

        // Add exam score column
        CRUD::addColumn([
            'name' => 'exam_score',
            'label' => 'Exam Score',
            'type' => 'text',
            'value' => function ($entry) {
                $latestResult = $entry->examResults()->latest()->first();
                if ($latestResult) {
                    return $latestResult->result . '%';
                }
                return 'N/A';
            }
        ]);
    }

    /**
     * Custom view for students with exam results
     */
    public function setupStudentsWithExamResultsView()
    {
        // Enable bulk operations for this view
        CRUD::enableBulkActions();

        // Filter students who have exam results
        CRUD::setQuery(\App\Models\User::whereHas('examResults'));

        // Add a custom column to show exam results count
        CRUD::addColumn([
            'name' => 'exam_results_count',
            'label' => 'Exam Results',
            'type' => 'text',
            'value' => function ($entry) {
                $resultsCount = $entry->examResults()->count();
                return $resultsCount . ' result(s)';
            }
        ]);

        // Add a column to show latest exam result
        CRUD::addColumn([
            'name' => 'latest_exam_result',
            'label' => 'Latest Result',
            'type' => 'text',
            'value' => function ($entry) {
                $latestResult = $entry->examResults()->latest()->first();
                if ($latestResult) {
                    return $latestResult->result . '%';
                }
                return 'N/A';
            }
        ]);

        // Add a column to show exam name
        CRUD::addColumn([
            'name' => 'exam_name',
            'label' => 'Exam Name',
            'type' => 'text',
            'value' => function ($entry) {
                $latestResult = $entry->examResults()->with('exam')->latest()->first();
                if ($latestResult && $latestResult->exam) {
                    return $latestResult->exam->title ?? 'N/A';
                }
                return 'N/A';
            }
        ]);

        // Add a column to show if student has taken multiple exams
        CRUD::addColumn([
            'name' => 'exams_taken',
            'label' => 'Exams Taken',
            'type' => 'text',
            'value' => function ($entry) {
                $examsTaken = $entry->userExams()->count();
                return $examsTaken > 0 ? $examsTaken . ' exam(s)' : 'No exams taken';
            }
        ]);

        // Add exam score column
        CRUD::addColumn([
            'name' => 'exam_score',
            'label' => 'Exam Score',
            'type' => 'text',
            'value' => function ($entry) {
                $latestResult = $entry->examResults()->latest()->first();
                if ($latestResult) {
                    return $latestResult->result . '%';
                }
                return 'N/A';
            }
        ]);

        // Add row actions only for this view
        CRUD::addButton('line', 'view_results', 'view', 'crud::buttons.view_results');
        CRUD::addButton('line', 'reset_result', 'view', 'crud::buttons.reset_result');
    }

    /**
     * Custom view for shortlisted students
     */
    public function setupShortlistedStudentsView()
    {
        // Enable bulk operations for this view
        CRUD::enableBulkActions();

        // Filter students who are shortlisted (shortlist = 1)
        CRUD::setQuery(\App\Models\User::where('shortlist', 1));

        // Add a custom column to show shortlist status
        CRUD::addColumn([
            'name' => 'shortlist_status',
            'label' => 'Shortlist Status',
            'type' => 'text',
            'value' => function ($entry) {
                return $entry->shortlist ? 'Shortlisted' : 'Not Shortlisted';
            }
        ]);

        // Add exam score column
        CRUD::addColumn([
            'name' => 'exam_score',
            'label' => 'Exam Score',
            'type' => 'text',
            'value' => function ($entry) {
                $latestResult = $entry->examResults()->latest()->first();
                if ($latestResult) {
                    return $latestResult->result . '%';
                }
                return 'N/A';
            }
        ]);

        // Add admission status column
        CRUD::addColumn([
            'name' => 'admission_status',
            'label' => 'Admission Status',
            'type' => 'text',
            'value' => function ($entry) {
                $admission = $entry->admissions()->whereNotNull('session')->first();
                if ($admission) {
                    return 'Admitted';
                }
                $pendingAdmission = $entry->admissions()->whereNull('session')->first();
                if ($pendingAdmission) {
                    return 'Pending Acceptance';
                }
                return 'Not Admitted';
            }
        ]);

        // Add course column
        CRUD::addColumn([
            'name' => 'registered_course',
            'label' => 'Registered Course',
            'type' => 'text',
            'value' => function ($entry) {
                if ($entry->registered_course && $entry->course) {
                    return $entry->course->course_name ?? 'N/A';
                }
                return 'N/A';
            }
        ]);

        // Add exams taken column
        CRUD::addColumn([
            'name' => 'exams_taken',
            'label' => 'Exams Taken',
            'type' => 'text',
            'value' => function ($entry) {
                $examsTaken = $entry->userExams()->count();
                return $examsTaken > 0 ? $examsTaken . ' exam(s)' : 'No exams taken';
            }
        ]);

        // Add the shortlist actions dropdown button (top)
        CRUD::addButtonFromView('top', 'bulk_shortlist_actions_dropdown', 'bulk_shortlist_actions_dropdown', 'beginning');

        // Remove default edit, preview, delete buttons and add custom row actions dropdown
        CRUD::removeButton('line', 'update');
        CRUD::removeButton('line', 'show');
        CRUD::removeButton('line', 'delete');
        CRUD::addButton('line', 'shortlist_row_actions_dropdown', 'view', 'crud::buttons.shortlist_row_actions_dropdown');
    }

    /**
     * Handle bulk admit operation via AJAX
     */
    public function bulkAdmit()
    {
        $request = request();
        $studentIds = $request->input('student_ids', []);
        $courseId = $request->input('course_id');
        $sessionId = $request->input('session_id');

        if (empty($studentIds) || !$courseId || !$sessionId) {
            return response()->json([
                'success' => false,
                'message' => 'Please select students, course, and session.'
            ], 400);
        }

        try {
            $students = \App\Models\User::whereIn('id', $studentIds)->get();
            $admittedCount = 0;

            foreach ($students as $student) {
                // Check if student already has an admission for this course/session
                $existingAdmission = $student->admissions()
                    ->where('course_id', $courseId)
                    ->where('session', $sessionId)
                    ->first();

                if (!$existingAdmission) {
                    // Create new admission
                    $student->admissions()->create([
                        'course_id' => $courseId,
                        'session' => $sessionId,
                        'confirmed' => now(),
                    ]);
                    $admittedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully admitted {$admittedCount} student(s)."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to admit students: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admit shortlisted students (bulk or single) via AJAX for Backpack Shortlist Actions.
     */
    public function admitShortlistedStudents(Request $request)
    {
        // If admit_all is set, admit all shortlisted students
        if ($request->input('admit_all')) {
            $validated = $request->validate([
                'course_id' => 'required|nullable|exists:courses,id',
                'session_id' => 'sometimes|nullable|exists:course_sessions,id',
            ]);
            $course = Course::find($validated['course_id']);
            $session = CourseSession::find($validated['session_id'] ?? '');
            if ($session && $session->course_id != $course->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not valid for selected course',
                ], 422);
            }
            $message = 'All shortlisted students admitted successfully';
            $admittedCount = 0;
            try {
                $users = User::where('shortlist', 1)->get();
                foreach ($users as $user) {
                    CreateStudentAdmissionJob::dispatch($user, $course, $session);
                    $admittedCount++;
                }
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'admitted_count' => $admittedCount,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to admit students: ' . $e->getMessage(),
                ], 500);
            }
        }

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
        $change = ($validated['change'] ?? false) == 'true';

        if ($session && $session->course_id != $course->id) {
            return response()->json([
                'success' => false,
                'message' => 'Session not valid for selected course',
            ], 422);
        }
        $message = 'Student(s) admitted successfully';
        $admittedCount = 0;
        try {
            if ($validated['user_id'] ?? false) {
                $user_id = $validated['user_id'];
                $user = User::where('userId', $user_id)->first();
                if ($user) {
                    CreateStudentAdmissionJob::dispatch($user, $course, $session);
                    $oldAdmission = UserAdmission::where('user_id', $user_id)->first();
                    if ($oldAdmission && $change) {
                        $message = 'Student admission changed successfully';
                    }
                    $admittedCount = 1;
                }
            } elseif (count($validated['user_ids'] ?? []) > 0) {
                $user_ids = $validated['user_ids'];
                foreach ($user_ids as $user_id) {
                    $user = User::where('userId', $user_id)->first();
                    if ($user) {
                        CreateStudentAdmissionJob::dispatch($user, $course, $session);
                        $admittedCount++;
                    }
                }
            }
            return response()->json([
                'success' => true,
                'message' => $message,
                'admitted_count' => $admittedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to admit students: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the exam result for a student (Backpack admin panel)
     */
    public function viewResult($id)
    {
        $student = \App\Models\User::find($id);
        if (!$student) {
            return back()->with(['flash' => 'Student not found.', 'key' => 'error']);
        }

        // Get the latest exam result for the student
        $latestResult = $student->examResults()->latest()->first();
        if (!$latestResult) {
            return back()->with(['flash' => 'No exam results found for this student.', 'key' => 'error']);
        }

        // Get the related exam info
        $exam = $latestResult->exam ?? null;
        if (!$exam) {
            return back()->with(['flash' => 'Exam information not found.', 'key' => 'error']);
        }

        $data = [
            'result_info' => $latestResult,
            'student_info' => $student,
            'exam_info' => $exam,
        ];
        return view('vendor.backpack.crud.admin_view_result', $data);
    }

    /**
     * Reset the exam result for a student (Backpack admin panel)
     */
    public function resetResult($exam_id, $user_id)
    {
        $user = \App\Models\User::findOrFail($user_id);
        if (!$user) {
            return back()->with(['flash' => 'Student not found.', 'key' => 'error']);
        }
        $exam = \App\Models\OexExamMaster::find($exam_id);
        if (!$exam) {
            return back()->with(['flash' => 'Exam not found.', 'key' => 'error']);
        }

        $user->updated_at = now();
        $user->save();

        \App\Models\UserExam::updateOrCreate(
            [
                'user_id' => $user_id,
                'exam_id' => $exam_id,
            ],
            ['started' => null, 'submitted' => null, 'exam_joined' => 0, 'std_status' => 1],
        );

        \App\Models\OexResult::where('user_id', $user_id)->where('exam_id', $exam_id)->delete();

        return redirect()->back()->with([
            'flash' => 'Exam reset successfully',
            'key' => 'success',
        ]);
    }

    /**
     * Return the count of all shortlisted students for AJAX bulk admit modal.
     */
    public function shortlistedCount(Request $request)
    {
        $count = User::where('shortlist', 1)->count();
        return response()->json(['count' => $count]);
    }

    // Remove the proxy methods for AJAX endpoints, as the trait methods are used directly.
}
