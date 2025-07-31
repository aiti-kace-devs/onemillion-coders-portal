<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\AdminRequest;
use App\Models\Admin;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\UserFieldHelpers;
use App\Helpers\WidgetHelper;
use Illuminate\Support\Facades\DB;
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
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }
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

        // Add permission checks
        $this->crud->operation(['list', 'show'], function () {
            $this->crud->addClause('where', function ($query) {
                if (!backpack_user()->can('admin.read.all')) {
                    $query->where('id', backpack_user()->id);
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
        if (!backpack_user()->can('admin.read.all')) {
            abort(403, 'Unauthorized action.');
        }

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
        // Check permissions
        if (!backpack_user()->can('admin.create')) {
            abort(403, 'Unauthorized action.');
        }

        // $this->crud->removeSaveAction('save_and_back');
        // $this->crud->removeSaveAction('save_and_new');
        // $this->crud->removeSaveAction('save_and_preview');
        // $this->crud->removeSaveAction('save_and_edit');

        $this->setupUserFields();
        $this->crud->setValidation(AdminRequest::class);
    }

    public function setupShowOperation()
    {
        // Check permissions
        if (!backpack_user()->can('admin.read.all')) {
            abort(403, 'Unauthorized action.');
        }

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
        // Check permissions
        if (!backpack_user()->can('admin.update.self')) {
            abort(403, 'Unauthorized action.');
        }

        $this->setupUserFields(false);
    }

    // Override the store method to handle course assignment
    public function store()
    {
        // Check permissions
        if (!backpack_user()->can('admin.create')) {
            abort(403, 'Unauthorized action.');
        }

        $response = $this->traitStore();
        
        // Get the created admin
        $admin = $this->crud->entry;
        
        // Handle password hashing
        if (request()->has('password') && request()->input('password')) {
            $admin->password = request()->input('password');
            $admin->save();
        }
        
        // Sync the assigned courses
        if (request()->has('courses')) {
            $admin->assignedCourses()->sync(request()->input('courses'));
        }
        
        return $response;
    }

    // Override the update method to handle course assignment
    public function update()
    {
        // Check permissions
        if (!backpack_user()->can('admin.update.self')) {
            abort(403, 'Unauthorized action.');
        }

        $response = $this->traitUpdate();
        
        // Get the updated admin
        $admin = $this->crud->entry;
        
        // Handle password hashing
        if (request()->has('password') && request()->input('password')) {
            $admin->password = request()->input('password');
            $admin->save();
        }
        
        // Sync the assigned courses
        if (request()->has('courses')) {
            $admin->assignedCourses()->sync(request()->input('courses'));
        }
        
        return $response;
    }

    /**
     * Define what happens when the Delete operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-delete
     * @return void
     */
    public function destroy($id)
    {
        // Check permissions
        if (!backpack_user()->can('admin.delete')) {
            abort(403, 'Unauthorized action.');
        }

        DB::beginTransaction();

        try {
            $admin = Admin::findOrFail($id);

            $admin->assignedCourses()->detach();

            $admin->delete();

            DB::commit();

            \Alert::success('Admin deleted successfully.')->flash();

            return redirect(backpack_url('admin/admin'));
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Admin deletion failed: ' . $e->getMessage());
            \Alert::error('Failed to delete admin: ' . $e->getMessage())->flash();

            return redirect(backpack_url('admin/admin'));
        }
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
