<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UserRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Http\Controllers\Traits\BulkStudentActionsTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\ShortlistActionsTrait;
use App\Http\Controllers\Traits\ShortlistRowActionsTrait;
use App\Models\UserAdmission;
use App\Models\User;
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
class ManageStudentCrudController extends CrudController
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
        CRUD::setRoute(config('backpack.base.route_prefix') . '/manage-student');
        CRUD::setEntityNameStrings('student', 'students');

        // CRUD::denyAccess('create');
        // CRUD::denyAccess('update');
        // $this->setupFilter();
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
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/manage-student');
        $this->crud->setEntityNameStrings('manage student', 'manage students');
        $this->setupFilter();

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
        // CRUD::disablePersistentTable();
        CRUD::addButtonFromView('top', 'student_views_dropdown', 'student_views_dropdown', 'beginning');
        CRUD::addButtonFromView('top', 'manage_student_bulk_actions_dropdown', 'manage_student_bulk_actions_dropdown', 'beginning');
        CRUD::addButton('top', 'assign_batch_bulk', 'view', 'admin.bulk.assign_batch', 'beginning');
        // Add userId column to the list view
        CRUD::addColumn([
            'name' => 'userId',
            'label' => 'User ID',
            'type' => 'text',
        ]);

        CRUD::enableBulkActions();

        // Add export options
        CRUD::enableExportButtons();
    }

    protected function setupShowOperation()
    {
        $this->crud->query->with(['admission', 'course', 'examResults.exam']);
        $this->setupManageStudentShowColumns();
        $this->crud->set('show.setFromDb', false);
        $this->crud->setShowView('vendor.backpack.crud.manage_student_show');

        // Add action buttons for the preview page
        CRUD::addButtonFromView('line', 'manage_student_actions', 'crud::buttons.manage_student_actions', 'end');
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






    public function setupFilter()
    {
        // $this->addStudentBatchFilter('Batch Filter');
        $this->courseFilter('registered_course');
        $this->addConfirmedAdmissionFilter();
        $this->addAdmissionLocationFilter();
        $this->addAdmittedAtFilter();
        FilterHelper::addBooleanFilter('shortlist', 'Shortlist');
        FilterHelper::addAgeRangeFilter();
        FilterHelper::addGenderFilter();
        FilterHelper::addBooleanColumn('shortlist', 'Shortlist');
        $this->addStudentBatchFilterFromDashboard('admission');
    }
    /**
     * Handle bulk admit operation via AJAX
     */
    public function bulkAdmit(Request $request)
    {
        $this->traitAdmitStudent($request);
    }

    /**
     * Admit shortlisted students (bulk or single) via AJAX for Backpack Shortlist Actions.
     */
    // public function admitShortlistedStudents(AdmitShortlistedStudentsRequest $request)
    // {
    //     $validated = $request->validated();

    //     // If admit_all is set, admit all shortlisted students
    //     if ($request->input('admit_all')) {
    //         $course = Course::find($validated['course_id']);
    //         $session = CourseSession::find($validated['session_id'] ?? '');
    //         if ($session && $session->course_id != $course->id) {
    //             return response()->json(
    //                 [
    //                     'success' => false,
    //                     'message' => 'Session not valid for selected course',
    //                 ],
    //                 422,
    //             );
    //         }
    //         $message = 'All shortlisted students admitted successfully';
    //         $admittedCount = 0;
    //         try {
    //             $users = User::where('shortlist', 1)->get();
    //             foreach ($users as $user) {
    //                 CreateStudentAdmissionJob::dispatch($user, $course, $session);
    //                 $admittedCount++;
    //             }
    //             return response()->json([
    //                 'success' => true,
    //                 'message' => $message,
    //                 'admitted_count' => $admittedCount,
    //             ]);
    //         } catch (\Exception $e) {
    //             return response()->json(
    //                 [
    //                     'success' => false,
    //                     'message' => 'Failed to admit students: ' . $e->getMessage(),
    //                 ],
    //                 500,
    //             );
    //         }
    //     }

    //     $course = Course::find($validated['course_id']);
    //     $session = CourseSession::find($validated['session_id'] ?? '');
    //     $change = ($validated['change'] ?? false) == 'true';

    //     if ($session && $session->course_id != $course->id) {
    //         return response()->json(
    //             [
    //                 'success' => false,
    //                 'message' => 'Session not valid for selected course',
    //             ],
    //             422,
    //         );
    //     }
    //     $message = 'Student(s) admitted successfully';
    //     $admittedCount = 0;
    //     try {
    //         if ($validated['user_id'] ?? false) {
    //             $user_id = $validated['user_id'];
    //             $user = User::where('userId', $user_id)->first();
    //             if ($user) {
    //                 CreateStudentAdmissionJob::dispatch($user, $course, $session);
    //                 $oldAdmission = UserAdmission::where('user_id', $user_id)->first();
    //                 if ($oldAdmission && $change) {
    //                     $message = 'Student admission changed successfully';
    //                 }
    //                 $admittedCount = 1;
    //             }
    //         } elseif (count($validated['user_ids'] ?? []) > 0) {
    //             $user_ids = $validated['user_ids'];
    //             foreach ($user_ids as $user_id) {
    //                 $user = User::where('userId', $user_id)->first();
    //                 if ($user) {
    //                     CreateStudentAdmissionJob::dispatch($user, $course, $session);
    //                     $admittedCount++;
    //                 }
    //             }
    //         }
    //         return response()->json([
    //             'success' => true,
    //             'message' => $message,
    //             'admitted_count' => $admittedCount,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json(
    //             [
    //                 'success' => false,
    //                 'message' => 'Failed to admit students: ' . $e->getMessage(),
    //             ],
    //             500,
    //         );
    //     }
    // }

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

    // Remove the proxy methods for AJAX endpoints, as the trait methods are used directly.
}
