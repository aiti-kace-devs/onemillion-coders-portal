<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UserRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Http\Requests\ChangeAdmissionRequest;
use App\Http\Requests\ChooseSessionRequest;
use App\Models\Branch;
use App\Models\Course;
use App\Models\User;
use App\Models\CourseSession;
use App\Models\District;
use App\Models\OexExamMaster;
use App\Models\UserAdmission;
use App\Helpers\UserFieldHelpers;
use App\Helpers\WidgetHelper;
use App\Helpers\FilterHelper;
use App\Helpers\CourseVisibilityHelper;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\GetsFilteredQuery;
use Illuminate\Support\Facades\Log;
/**
 * Class UserCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ManageStudentCrudController extends CrudController
{
    use \App\Http\Controllers\Traits\BulkStudentActionsTrait {
        admitStudent as traitAdmitStudent;
    }
    use \App\Http\Controllers\Traits\ShortlistActionsTrait;
    use \App\Http\Controllers\Traits\ShortlistRowActionsTrait {
        changeAdmission as traitChangeAdmission;
        chooseSession as traitChooseSession;
        deleteAdmission as traitDeleteAdmission;
    }
    use \App\SearchableCRUD;
    use UserFieldHelpers;
    use GetsFilteredQuery;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/manage-student');
        CRUD::setEntityNameStrings('student', 'students');

        // CRUD::denyAccess('create');
        // CRUD::denyAccess('update');
        // $this->setupFilter();
        $this->setSearchableColumns(['name', 'email', 'mobile_no']);
        $this->setSearchResultAttributes(['id', 'name', 'email', 'mobile_no']);

        $this->crud->denyAccess('create');
        // $this->crud->denyAccess('update');
        // $this->crud->denyAccess('delete');

        // Apply course visibility scope on list/show for non-super admins.
        $this->crud->operation(['list', 'show'], function () {
            $this->applyCurrentAdminUserCourseScope();
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

        WidgetHelper::userStatisticsWidget();
        if (!backpack_user()->can('student.update.all')) {
            abort(403, 'Unauthorized action.');
        }

        $this->crud->setModel(User::class);
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/manage-student');
        $this->crud->setEntityNameStrings('manage student', 'manage students');
        $this->setupFilter();
        $this->applyCurrentAdminUserCourseScope();

        $this->crud->query->select([
            'id',
            'userId',
            'registered_course',
            'shortlist',
            'name',
            'gender',
            'age',
            'email',
            'mobile_no',
            'ghcard',
        ]);
        $this->addConfirmedAdmissionColumn();
        View::share('mailable', \App\Helpers\MailerHelper::getMailableClasses());
        $this->setupStudentColumns();
        // CRUD::disablePersistentTable();
        CRUD::addButtonFromView('top', 'student_views_dropdown', 'student_views_dropdown', 'beginning');
        CRUD::addButtonFromView('top', 'bulk_actions_dropdown', 'bulk_actions_dropdown', 'beginning');
        CRUD::addButton('top', 'assign_batch_bulk', 'view', 'admin.bulk.assign_batch', 'beginning');
        // Add userId column to the list view
        CRUD::addColumn([
            'name' => 'userId',
            'label' => 'User ID',
            'type' => 'text',
        ]);

        CRUD::enableBulkActions();

        CRUD::enableExportButtons();

        CRUD::removeButton('update', 'line');
        CRUD::removeButton('delete', 'line');
    }

    protected function setupShowOperation()
    {
        $this->crud->set('show.setFromDb', false);
        
        $this->crud->setShowView('vendor.backpack.crud.manage_student_show');

        CRUD::addButtonFromView('line', 'manage_student_actions', 'view', 'crud::buttons.manage_student_actions', 'end');

        $visibleCourseIds = CourseVisibilityHelper::currentAdminVisibleCourseIds();
        
        $coursesQuery = Course::query()
            ->with('centre')
            ->whereHas('batch', function ($query) {
                $query->where('completed', false)
                    ->where('status', true);
            })
            ->orderBy('course_name');

        if (is_array($visibleCourseIds)) {
            if (empty($visibleCourseIds)) {
                $courses = collect();
                $sessions = collect();

                View::share([
                    'courses' => $courses,
                    'sessions' => $sessions,
                ]);

                return;
            }

            $coursesQuery->whereIn('id', $visibleCourseIds);
        }

        $courses = $coursesQuery
            ->get()
            ->mapWithKeys(fn (Course $course) => [$course->id => $course->display_name]);

        $sessionsQuery = CourseSession::query();
        if (is_array($visibleCourseIds)) {
            $sessionsQuery->whereIn('course_id', $visibleCourseIds);
        }
        $sessions = $sessionsQuery->get();

        View::share([
            'courses' => $courses,
            'sessions' => $sessions,
        ]);
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

        $this->setupStudentUpdateFields();
    }

    /**
     * Setup organized student update fields with tabs
     */
    protected function setupStudentUpdateFields()
    {
        // Personal Information Tab
        CRUD::addField([
            'name' => 'personal_info_header',
            'type' => 'custom_html',
            'value' => '<h5 class="mb-3"><i class="la la-user"></i> Personal Information</h5>',
            'tab' => 'Personal Info',
        ]);
        
        CRUD::field('name')
            ->type('text')
            ->label('Full Name')
            ->tab('Personal Info');
            
        CRUD::field('gender')
            ->type('select2')
            ->label('Gender')
            ->options([
                'male' => 'Male',
                'female' => 'Female',
            ])
            ->tab('Personal Info');
            
        CRUD::field('age')
            ->type('number')
            ->label('Age')
            ->tab('Personal Info');
            
        CRUD::field('ghcard')
            ->type('text')
            ->label('Ghana Card Number')
            ->tab('Personal Info');

        // Contact Information Tab
        CRUD::addField([
            'name' => 'contact_info_header',
            'type' => 'custom_html',
            'value' => '<h5 class="mb-3"><i class="la la-envelope"></i> Contact Information</h5>',
            'tab' => 'Contact Info',
        ]);
        
        CRUD::field('email')
            ->type('email')
            ->label('Email')
            ->tab('Contact Info');
            
        CRUD::field('mobile_no')
            ->type('text')
            ->label('Mobile Number')
            ->tab('Contact Info');

        // Course Information Tab
        CRUD::addField([
            'name' => 'course_info_header',
            'type' => 'custom_html',
            'value' => '<h5 class="mb-3"><i class="la la-graduation-cap"></i> Course Information</h5>',
            'tab' => 'Course Info',
        ]);
        
        CRUD::field('registered_course')
            ->type('text')
            ->label('Registered Course')
            ->tab('Course Info');
            
        CRUD::addField([
            'name' => 'shortlist',
            'type' => 'checkbox',
            'label' => 'Shortlisted',
            'tab' => 'Course Info',
        ]);
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






    public function setupFilter()
    {
        // $this->addStudentBatchFilter('Batch Filter');
        $this->addCurrentAdminCourseFilter('registered_course');
        $this->addConfirmedAdmissionFilter();
        $this->addRegionFilter();
        // $this->addDistrictFilter();
        $this->centreFilter();
        $this->addAdmittedAtFilter();
        FilterHelper::addBooleanFilter('shortlist', 'Shortlist');
        FilterHelper::addAgeRangeFilter();
        FilterHelper::addGenderFilter();
        FilterHelper::addBooleanColumn('shortlist', 'Shortlist');
        $this->addStudentBatchFilterFromDashboard('admission');
    }

    private function addRegionFilter(): void
    {
        $regions = Branch::query()
            ->orderBy('title')
            ->pluck('title', 'id')
            ->toArray();

        FilterHelper::addSelectFilter(
            columnName: 'region_filter',
            label: 'Region',
            options: $regions,
            type: 'select2',
            callback: function ($branchId) {
                CRUD::addClause('whereHas', 'admissions.course.centre', function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                });
            }
        );
    }

    private function addDistrictFilter(): void
    {
        $districts = District::query()
            ->orderBy('title')
            ->pluck('title', 'id')
            ->toArray();

        FilterHelper::addSelectFilter(
            columnName: 'district_filter',
            label: 'District',
            options: $districts,
            type: 'select2',
            callback: function ($districtId) {
                CRUD::addClause('whereHas', 'admissions.course.centre.districts', function ($query) use ($districtId) {
                    $query->where('districts.id', $districtId);
                });
            }
        );
    }
    /**
     * Handle bulk admit operation via AJAX
     */
    public function bulkAdmit(Request $request)
    {
        $this->traitAdmitStudent($request);
    }



    /**
     * Show the exam result for a student (Backpack admin panel)
     */
    public function viewResult($id)
    {
        $student = User::find($id);
        if (!$student) {
            return back()->with(['flash' => 'Student not found.', 'key' => 'error']);
        }

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
        $user = User::findOrFail($user_id);
        if (!$user) {
            return back()->with(['flash' => 'Student not found.', 'key' => 'error']);
        }
        $exam = OexExamMaster::find($exam_id);
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

        return redirect()
            ->back()
            ->with([
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

    public function getFilteredCount(Request $request)
    {
        $customView = $request->input('custom_view');
        if ($customView === 'setupStudentsWithExamResultsView' || $customView === 'students-with-exam-results') {
            $query = User::whereHas('examResults');
        } else {
            $query = User::query();
        }
        $count = $query->count();
        return response()->json(['count' => $count, 'custom_view' => $customView]);
    }

    public function deleteAdmission($user_id)
    {
        try {
            $user = User::findOrFail($user_id);
            $user->admissions()->delete();
            $user->shortlist = false;
            $user->save();

            return response()->json(['message' => 'Admission deleted successfully.']);
        } catch (\Exception $e) {
            \Log::error('Error deleting admission:', ['user_id' => $user_id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to delete admission.'], 500);
        }
    }

    /**
     * Change admission for a student
     */
    public function changeAdmission(ChangeAdmissionRequest $request, $userId)
    {
        return $this->traitChangeAdmission($request, $userId);
    }

    /**
     * Choose session for a student
     */
    public function chooseSession(ChooseSessionRequest $request, $userId)
    {
        return $this->traitChooseSession($request, $userId);
    }

    /**
     * Get courses list for dropdown (filtered by active batches)
     */
    public function getCoursesAjax()
    {
        $coursesQuery = Course::query()
            ->with('centre')
            ->whereHas('batch', function ($query) {
                $query->where('completed', false)
                    ->where('status', true);
            })
            ->orderBy('course_name');

        $visibleCourseIds = CourseVisibilityHelper::currentAdminVisibleCourseIds();
        if (is_array($visibleCourseIds)) {
            if (empty($visibleCourseIds)) {
                return response()->json([]);
            }
            $coursesQuery->whereIn('id', $visibleCourseIds);
        }

        $courses = $coursesQuery
            ->get()
            ->map(fn (Course $course) => [
                'id' => $course->id,
                'course_name' => $course->course_name,
                'display_name' => $course->display_name,
            ])
            ->values();

        return response()->json($courses);
    }

    /**
     * Get sessions list for dropdown (filtered by course)
     */
    public function getSessionsAjax(Request $request)
    {
        $courseId = $request->input('course_id');

        $visibleCourseIds = CourseVisibilityHelper::currentAdminVisibleCourseIds();
        if (is_array($visibleCourseIds) && ! in_array((int) $courseId, $visibleCourseIds, true)) {
            return response()->json([]);
        }

        $sessions = CourseSession::where('course_id', $courseId)
            ->select('id', 'name', 'course_id')
            ->get();
        return response()->json($sessions);
    }

    /**
     * Get student metrics for preview page
     */
    public function getStudentMetrics($userId)
    {
        $user = User::findOrFail($userId);
        
        // Basic info
        $basicInfo = [
            'name' => $user->name,
            'email' => $user->email,
            'mobile_no' => $user->mobile_no,
            'gender' => $user->gender,
            'age' => $user->age,
            'registered_course' => $user->registered_course,
        ];
        
        // Admission info
        $admission = $user->admissions()->first();
        $admissionInfo = [
            'has_admission' => $admission ? true : false,
            'course_id' => $admission?->course_id,
            'session_id' => $admission?->session,
            'confirmed' => $admission?->confirmed,
            'batch_id' => $admission?->batch_id,
            'location' => $admission?->location,
        ];
        
        // Exam results
        $examResults = $user->examResults()->with('exam')->get();
        $examMetrics = [
            'total_exams' => $examResults->count(),
            'results' => $examResults->map(function($result) {
                return [
                    'exam_name' => $result->exam?->title ?? 'N/A',
                    'score' => $result->yes_ans,
                    'total' => $result->yes_ans + $result->no_ans,
                    'percentage' => ($result->yes_ans + $result->no_ans) > 0 
                        ? round(($result->yes_ans / ($result->yes_ans + $result->no_ans)) * 100, 2) 
                        : 0,
                    'attempted_at' => $result->created_at,
                ];
            }),
            'latest_score_percentage' => $examResults->first() ? (
                ($examResults->first()->yes_ans + $examResults->first()->no_ans) > 0
                    ? round(($examResults->first()->yes_ans / ($examResults->first()->yes_ans + $examResults->first()->no_ans)) * 100, 2)
                    : 0
            ) : null,
        ];
        
        // Attendance
        $attendanceRecords = $user->attendances()->get();
        $attendanceMetrics = [
            'total_sessions' => $attendanceRecords->count(),
            'present' => $attendanceRecords->where('status', 'present')->count(),
            'absent' => $attendanceRecords->where('status', 'absent')->count(),
            'late' => $attendanceRecords->where('status', 'late')->count(),
            'excused' => $attendanceRecords->where('status', 'excused')->count(),
            'attendance_rate' => $attendanceRecords->count() > 0 
                ? round(($attendanceRecords->where('status', 'present')->count() / $attendanceRecords->count()) * 100, 2) 
                : 0,
            'records' => $attendanceRecords->map(function($record) {
                return [
                    'date' => $record->created_at,
                    'status' => $record->status,
                    'check_in_time' => $record->check_in_time,
                ];
            }),
        ];
        
        return response()->json([
            'basic_info' => $basicInfo,
            'admission_info' => $admissionInfo,
            'exam_metrics' => $examMetrics,
            'attendance_metrics' => $attendanceMetrics,
        ]);
    }

    // Remove the proxy methods for AJAX endpoints, as the trait methods are used directly.
}
