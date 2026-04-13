<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\MasterSessionRequest;
use App\Models\MasterSession;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class MasterSessionCrudController
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class MasterSessionCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(MasterSession::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/master-session');
        CRUD::setEntityNameStrings('master session', 'master sessions');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('master_name')->label('Name');
        CRUD::column('session_type')->label('Session Type');
        CRUD::column('time')->label('Time');
        CRUD::column('course_type')->label('Course Type');
        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
            'toggleable' => true,
            'toggle_url' => 'master-session/{id}/toggle-status',
        ]);
        CRUD::column('created_at');
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(MasterSessionRequest::class);

        CRUD::addField([
            'name' => 'master_name',
            'label' => 'Session Name',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        CRUD::addField([
            'name' => 'session_type',
            'label' => 'Session Type',
            'type' => 'select_from_array',
            'options' => [
                'Morning' => 'Morning',
                'Afternoon' => 'Afternoon',
                'Evening' => 'Evening',
                'Fullday' => 'Fullday',
                'Online' => 'Online',
            ],
            'allows_null' => false,
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        CRUD::addField([
            'name' => 'time',
            'label' => 'Time / Duration',
            'type' => 'text',
            'hint' => 'e.g. 8am - 1pm',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        CRUD::addField([
            'name' => 'course_type',
            'label' => 'Course Type',
            'type' => 'text',
            'default' => 'standard',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        CRUD::addField([
            'name' => 'status',
            'label' => 'Active',
            'type' => 'checkbox',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    /**
     * Toggle the status of a master session (soft-delete strategy).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus()
    {
        $this->crud->hasAccessOrFail('update');

        $id = request()->route('id');
        $data = request()->validate(['value' => 'required|boolean']);

        $masterSession = MasterSession::findOrFail($id);
        $masterSession->status = (bool) $data['value'];
        $masterSession->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Master session status updated successfully.',
            'value' => $masterSession->status ? 1 : 0,
        ]);
    }
}
