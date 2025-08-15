<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\AttendanceRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\WidgetHelper;
use App\Helpers\FilterHelper;
use App\Models\Course;
use App\Helpers\CourseFieldHelpers;

/**
 * Class AttendanceCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class AttendanceCrudController extends CrudController
{

    use CourseFieldHelpers;
    use \App\SearchableCRUD;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \App\Http\Controllers\Traits\AttendanceQRCodeTrait;
    use \App\Http\Controllers\Traits\AttendanceRecordTrait;
    use \App\Http\Controllers\Traits\AttendanceConfirmTrait;
    use \App\Http\Controllers\Traits\AttendanceViewRemoveTrait;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Attendance::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/attendance');
        CRUD::setEntityNameStrings('attendance', 'attendances');

        $this->setSearchableColumns(['user_id', 'date']);
        $this->setSearchResultAttributes(['id', 'user_id', 'date']);

        $this->crud->denyAccess('create');

        // Add permission checks
        $this->crud->operation(['list', 'show'], function () {
            $this->crud->addClause('where', function ($query) {
                if (!backpack_user()->can('attendance.read.all')) {
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
        if (!backpack_user()->can('attendance.read.all')) {
            abort(403, 'Unauthorized action.');
        }

        CRUD::column('user_id')->label('Student')->linkTo('user.show');
        FilterHelper::addGenericRelationshipColumn('user', 'Email', 'user', 'email');
        FilterHelper::addGenericRelationshipColumn('course', 'Course', 'course', 'course_name');
        CRUD::addColumn([
            'name' => 'courseSession.session',
            'label' => 'Session',
            'type' => 'text',
        ]);

        CRUD::column('date');
        $this->addStudentBatchFilter('userAdmission', 'Student Batch');
        $this->courseFilter('course_id');
        $sessions = \App\Models\CourseSession::select('session')
            ->distinct()
            ->pluck('session', 'session')
            ->toArray();

        FilterHelper::addSelectFilter('session', 'Filter Session', $sessions, 'select2', function($value) {
            CRUD::addClause('whereHas', 'courseSession', function($query) use ($value) {
                $query->where('course_sessions.session', $value);
            });
        });

        FilterHelper::addDateRangeFilter('date', 'Filter BY Date');
        CRUD::enableExportButtons();
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
        if (!backpack_user()->can('attendance.create')) {
            abort(403, 'Unauthorized action.');
        }

        CRUD::setValidation(AttendanceRequest::class);
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
        if (!backpack_user()->can('attendance.update.all')) {
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
        if (!backpack_user()->can('attendance.delete')) {
            abort(403, 'Unauthorized action.');
        }
    }

    // Example: Add endpoints or methods for trait logic
    public function setupGenerateQrCodeData(AttendanceRequest $request)
    {
        $data = $request->validated();
        return response()->json($this->generateQRCodeDataLogic($data));
    }

    public function setupRecordAttendance(AttendanceRequest $request)
    {
        $data = $request->validated();
        $result = $this->recordAttendanceLogic($data['scanned_data']);
        return response()->json($result);
    }

    public function setupConfirmAttendance(AttendanceRequest $request)
    {
        $data = $request->validated();
        $result = $this->confirmAttendanceLogic($data, auth()->user());
        return response()->json($result);
    }

    public function setupViewAttendance()
    {
        $userId = auth()->user()->userId;
        $attendance = $this->viewAttendanceLogic($userId);
        return response()->json(['attendance' => $attendance]);
    }

    public function setupRemoveAttendance($id)
    {
        $result = $this->removeAttendanceLogic($id);
        return response()->json($result);
    }

    public function setupScanQrCodePage()
    {
        // $courses = auth('admin')->user()->assignedCourses()->get();
        $courses = Course::myAssignedCourses()->get()->groupBy('location');

        return view('vendor.backpack.ui.qr-scanner', [
            'groupedCourses' => $courses,
        ]);
    }
}
