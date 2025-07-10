<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\AdminRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class AdminCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class AdminCrudController extends CrudController
{
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
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::setFromDb();
        // Add any custom columns or logic here if needed
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(AdminRequest::class);
        CRUD::setFromDb();
        // Add custom fields if needed
        // Add a model event for after save to handle roles, permissions, courses, is_super
        CRUD::addField([
            'name' => 'roles',
            'type' => 'select2_multiple',
            'entity' => 'roles',
            'model' => 'Spatie\\Permission\\Models\\Role',
            'attribute' => 'name',
            'pivot' => true,
        ]);
        CRUD::addField([
            'name' => 'permissions',
            'type' => 'select2_multiple',
            'entity' => 'permissions',
            'model' => 'Spatie\\Permission\\Models\\Permission',
            'attribute' => 'name',
            'pivot' => true,
        ]);
        CRUD::addField([
            'name' => 'courses',
            'type' => 'select2_multiple',
            'entity' => 'assignedCourses',
            'model' => 'App\\Models\\Course',
            'attribute' => 'course_name',
            'pivot' => true,
        ]);
        CRUD::addField([
            'name' => 'is_super',
            'type' => 'checkbox',
            'label' => 'Super Admin',
        ]);
        // Hash password and handle custom logic after save
        CRUD::operation('create', function () {
            \Event::listen('eloquent.created: App\\Models\\Admin', function ($admin) {
                if (request()->filled('password')) {
                    $admin->password = \Illuminate\Support\Facades\Hash::make(request('password'));
                }
                $admin->is_super = request()->has('is_super') ? true : false;
                $admin->save();
                if (request()->has('roles')) {
                    $admin->syncRoles(request('roles'));
                }
                if (request()->has('permissions')) {
                    $admin->syncPermissions(request('permissions'));
                }
                if (request()->has('courses')) {
                    $admin->assignedCourses()->sync(request('courses'));
                }
            });
        });
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        CRUD::setValidation(AdminRequest::class);
        CRUD::setFromDb();
        // Add custom fields if needed (same as in setupCreateOperation)
        CRUD::addField([
            'name' => 'roles',
            'type' => 'select2_multiple',
            'entity' => 'roles',
            'model' => 'Spatie\\Permission\\Models\\Role',
            'attribute' => 'name',
            'pivot' => true,
        ]);
        CRUD::addField([
            'name' => 'permissions',
            'type' => 'select2_multiple',
            'entity' => 'permissions',
            'model' => 'Spatie\\Permission\\Models\\Permission',
            'attribute' => 'name',
            'pivot' => true,
        ]);
        CRUD::addField([
            'name' => 'courses',
            'type' => 'select2_multiple',
            'entity' => 'assignedCourses',
            'model' => 'App\\Models\\Course',
            'attribute' => 'course_name',
            'pivot' => true,
        ]);
        CRUD::addField([
            'name' => 'is_super',
            'type' => 'checkbox',
            'label' => 'Super Admin',
        ]);
        // Hash password and handle custom logic after update
        CRUD::operation('update', function () {
            \Event::listen('eloquent.updated: App\\Models\\Admin', function ($admin) {
                if (request()->filled('password')) {
                    $admin->password = \Illuminate\Support\Facades\Hash::make(request('password'));
                    $admin->save();
                }
                $admin->is_super = request()->has('is_super') ? true : false;
                $admin->save();
                if (request()->has('roles')) {
                    $admin->syncRoles(request('roles'));
                }
                if (request()->has('permissions')) {
                    $admin->syncPermissions(request('permissions'));
                }
                if (request()->has('courses')) {
                    $admin->assignedCourses()->sync(request('courses'));
                }
            });
        });
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
