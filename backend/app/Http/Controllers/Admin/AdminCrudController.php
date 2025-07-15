<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\AdminRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\UserFieldHelpers;
use App\Helpers\WidgetHelper;
/**
 * Class AdminCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class AdminCrudController extends CrudController
{

    use \App\SearchableCRUD;
    use UserFieldHelpers;
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
        CRUD::setModel(\App\Models\Admin::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/admin');
        CRUD::setEntityNameStrings('admin', 'admins');

        $this->setSearchableColumns(['email', 'name']);
        $this->setSearchResultAttributes(['id', 'email', 'name']);

        $this->crud->operation('list', function () {
            WidgetHelper::adminStatisticsWidget();
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
        // CRUD::setFromDb();
        $this->setupUserColumns();
        $this->setupUserFilters();
        CRUD::removeColumn('permissions');
        CRUD::enableExportButtons();
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */

    public function setupCreateOperation()
    {

        // $this->crud->removeSaveAction('save_and_back');
        // $this->crud->removeSaveAction('save_and_new');
        // $this->crud->removeSaveAction('save_and_preview');
        // $this->crud->removeSaveAction('save_and_edit');

        $this->setupUserFields();
        $this->crud->setValidation(AdminRequest::class);
    }


    public function setupShowOperation()
    {
        $this->setupUserColumns();
        $entry = $this->crud->getCurrentEntry();

        if ($entry->userProfile) {
            $this->setupProfileColumns();
        }
        
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupUserFields(false);
    }

    // No need for setupDeleteOperation unless you want to add custom logic before/after delete
    // Keep only custom endpoints that are not standard CRUD
    // Toggle is_super admin status
    public function setupIsSuperAdminStatus($id)
    {
        $admin = \App\Models\Admin::where('id', $id)->first();
        $admin->is_super = $admin->is_super == 1 ? 0 : 1;
        $admin->update();
    }

    // Get admin's assigned courses
    public function setupGetAdminCourses(\App\Models\Admin $admin)
    {
        return response()->json($admin->assignedCourses->pluck('id'));
    }

    // Update admin's assigned courses
    public function setupUpdateAdminCourses(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'admin_id' => 'required|exists:admins,id',
            'courses' => 'array',
            'courses.*' => 'exists:courses,id',
        ]);
        $admin = \App\Models\Admin::find($request->admin_id);
        $admin->assignedCourses()->sync($request->courses);
        return response()->json(['success' => true]);
    }
}
