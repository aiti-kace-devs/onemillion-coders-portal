<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UserRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\CrudPanel\Hooks\Facades\LifecycleHook;
use App\Http\Controllers\Traits\BulkStudentActionsTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\ShortlistActionsTrait;
use App\Http\Controllers\Traits\ShortlistRowActionsTrait;
use App\Models\UserAdmission;
use App\Models\User;
use App\Models\CourseBatch;
use App\Helpers\UserFieldHelpers;
use App\Helpers\WidgetHelper;
use App\Helpers\FilterHelper;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Traits\GetsFilteredQuery;
use Illuminate\Support\Facades\Log;

/**
 * Class UserCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserCrudController extends CrudController
{
    use BulkStudentActionsTrait {
        admitStudent as traitAdmitStudent;
    }
    use ShortlistActionsTrait;
    use ShortlistRowActionsTrait;
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
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user');
        CRUD::setEntityNameStrings('student', 'students');
        $this->setSearchableColumns(['name', 'email', 'mobile_no']);
        $this->setSearchResultAttributes(['id', 'name', 'email', 'mobile_no']);

        $this->crud->denyAccess('create');
        // $this->crud->denyAccess('update');
        // $this->crud->denyAccess('delete');
        // $this->crud->denyAccess('show');

        // Add permission checks
        LifecycleHook::hookInto(['list:before_setup', 'show:before_setup'], function () {
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
        WidgetHelper::userStatisticsWidget();

        // Check permissions
        if (!backpack_user()->can('student.read.all')) {
            abort(403, 'Unauthorized action.');
        }

        $this->crud->setModel(User::class);
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/user');
        $this->crud->setEntityNameStrings('student', 'students');
        $this->setupFilter();
        $this->applyCurrentAdminUserCourseScope();

        // Ensure we load the fields needed for relationships & columns
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
        // CRUD::addButtonFromView('top', 'student_views_dropdown', 'student_views_dropdown', 'beginning');
        // CRUD::addButtonFromView('top', 'bulk_actions_dropdown', 'bulk_actions_dropdown', 'beginning');
        // CRUD::addButton('top', 'assign_batch_bulk', 'view', 'admin.bulk.assign_batch', 'beginning');

        // Add userId column to the list view
        // CRUD::addColumn([
        //     'name' => 'userId',
        //     'label' => 'User ID',
        //     'type' => 'text',
        // ]);
        // CRUD::enableBulkActions();
        // CRUD::enableExportButtons();

        CRUD::removeButton('update', 'line');
        CRUD::removeButton('delete', 'line');
        CRUD::removeButton('show', 'line');
        CRUD::addButtonFromView('line', 'user_preview_manage_student', 'user_preview_manage_student', 'beginning');
    }

    protected function setupShowOperation()
    {
        // $this->setupShowStudentColumns();
        $this->crud->set('show.setFromDb', false);

        $this->crud->setShowView('vendor.backpack.crud.manage_student_show');

        CRUD::addButtonFromView('line', 'manage_student_actions', 'view', 'crud::buttons.manage_student_actions', 'end');
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

        CRUD::field('ghcard')->label('Ghana Card Number')->hint('Format: GHA-XXXXXXXXX-X');
        CRUD::field('is_verification_blocked')->type('boolean')->label('Ghana Card Verification Blocked')->hint('Check to block further automated verification attempts');
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

        /** @var \App\Models\User $user */
        $user = $this->crud->getCurrentEntry();
        if ($user && $user->isVerifiedByGhanaCard()) {
            $this->crud->field('first_name')->attributes(['readonly' => 'readonly', 'placeholder' => 'Verified by NIA']);
            $this->crud->field('last_name')->attributes(['readonly' => 'readonly', 'placeholder' => 'Verified by NIA']);
            $this->crud->field('middle_name')->attributes(['readonly' => 'readonly', 'placeholder' => 'Verified by NIA']);
            $this->crud->field('name')->attributes(['readonly' => 'readonly', 'placeholder' => 'Verified by NIA']);
            $this->crud->field('ghcard')->attributes(['readonly' => 'readonly']);
            $this->crud->field('date_of_birth')->attributes(['readonly' => 'readonly']);

            // Helpful hint for the admin
            $this->crud->field('is_verification_blocked')->hint('User is already verified. Changes to personal name fields are disabled.');
        }
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
        $notFound = [];
        $studentIds = $request->student_ids;

        $userIds = User::whereIn('id', $studentIds)->pluck('userId')->toArray();

        foreach (array_chunk($userIds, 100) as $chunk) {
            $admissions = UserAdmission::whereIn('user_id', $chunk)->get();

            foreach ($admissions as $admission) {
                $course = \App\Models\Course::find($admission->course_id);

                if ($course && (int) $course->batch_id === (int) $request->batch_id) {
                    $updated++;
                } else {
                    $notFound[] = $admission->user_id;
                }
            }
        }

        if ($updated === 0) {
            if (!empty($notFound)) {
                return response()->json([
                    'message' => 'No admissions updated. No matching courses with batch_id found for the students.',
                    'not_found' => $notFound
                ], 400);
            }
            return response()->json(['message' => 'No admissions updated.'], 400);
        }

        $message = 'Batch assignment successful';
        if (!empty($notFound)) {
            $message .= '. However, ' . count($notFound) . ' students could not be updated because no matching courses with batch_id were found.';
        }

        return response()->json(['message' => $message, 'updated' => $updated]);
    }

    public function setupFilter()
    {
        // $this->addStudentBatchFilter('Batch Filter');
        $this->addCurrentAdminCourseFilter('registered_course');
        $this->addConfirmedAdmissionFilter();
        // $this->addAdmissionLocationFilter();
        // $this->addAdmittedAtFilter();
        // FilterHelper::addBooleanFilter('shortlist', 'Shortlist');
        FilterHelper::addAgeRangeFilter();
        FilterHelper::addGenderFilter();
        FilterHelper::addBooleanColumn('shortlist', 'Shortlist');
        // if (backpack_user()->is_super) {
        //     $this->addStudentBatchFilterFromDashboard('admission');
        // }
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
        $student = \App\Models\User::findOrFail($id);
        if (!$student) {
            return back()->with(['flash' => 'Student not found.', 'key' => 'error']);
        }

        $latestResult = $student->examResults()->latest()->first();
        if (!$latestResult) {
            return back()->with(['flash' => 'No exam results found for this student.', 'key' => 'error']);
        }
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

    public function getActivities($user_id)
    {
        $user = User::findOrFail($user_id);
        $activities = $user->actions()->latest()->get();
        return view('admin.users.activities', compact('user', 'activities'));
    }
}
