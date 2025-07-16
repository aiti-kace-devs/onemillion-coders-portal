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
        CRUD::setEntityNameStrings('view attendance list', 'view attendance list');

        CRUD::denyAccess('create');
        CRUD::denyAccess('show');
        CRUD::denyAccess('update');

        $this->crud->operation('list', function () {
            WidgetHelper::attendanceWidgets();
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
        CRUD::column('user_id')->label('Student')->linkTo('user.show');
        FilterHelper::addGenericRelationshipColumn('user', 'Email', 'user', 'email');
        FilterHelper::addGenericRelationshipColumn('course', 'Course', 'course', 'course_name');
        CRUD::addColumn([
            'name' => 'courseSession.session',
            'label' => 'Session',
            'type' => 'text',
        ]);

        CRUD::column('date');
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
        $this->setupCreateOperation();
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
