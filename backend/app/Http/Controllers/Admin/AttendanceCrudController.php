<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\AttendanceRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class AttendanceCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class AttendanceCrudController extends CrudController
{
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
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::setFromDb(); // set columns from db columns.

        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */
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
    public function generateQRCodeData(AttendanceRequest $request)
    {
        $data = $request->validate($request->rulesForQRCode());
        return response()->json($this->generateQRCodeDataLogic($data));
    }

    public function recordAttendance(AttendanceRequest $request)
    {
        $data = $request->validate($request->rulesForRecordAttendance());
        $result = $this->recordAttendanceLogic($data['scanned_data']);
        return response()->json($result);
    }

    public function confirmAttendance(AttendanceRequest $request)
    {
        $data = $request->validate($request->rulesForConfirmAttendance());
        $result = $this->confirmAttendanceLogic($data, auth()->user());
        return response()->json($result);
    }

    public function viewAttendance()
    {
        $userId = auth()->user()->userId;
        $attendance = $this->viewAttendanceLogic($userId);
        return response()->json(['attendance' => $attendance]);
    }

    public function removeAttendance($id)
    {
        $result = $this->removeAttendanceLogic($id);
        return response()->json($result);
    }
}
