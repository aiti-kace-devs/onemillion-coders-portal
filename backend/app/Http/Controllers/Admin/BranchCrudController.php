<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\BranchRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\GeneralFieldsAndColumns;
use App\Helpers\FilterHelper;
use App\Helpers\WidgetHelper;
use App\Helpers\CrudListHelper;
use App\Services\CourseMatchReferenceService;

/**
 * Class BranchCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class BranchCrudController extends CrudController
{
    use GeneralFieldsAndColumns;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation {
        destroy as traitDestroy;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Branch::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/branch');
        CRUD::setEntityNameStrings('Region', 'Regions');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CrudListHelper::editInDropdown();
        WidgetHelper::branchStatisticsWidget();

        CRUD::column('title');
        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
        ]);
        CRUD::column('created_at');
        FilterHelper::addBooleanFilter('status');
        FilterHelper::addDateRangeFilter('created_at', 'Created At');
        CRUD::enableExportButtons();

        CRUD::denyAccess('show');

    }

    protected function setupShowOperation()
    {
        CRUD::set('show.setFromDb', false);
        CRUD::set('show.view', 'vendor.backpack.crud.branch_show');
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(BranchRequest::class);
        CRUD::field('title')->type('text');
        $this->addIsActiveField([true  => 'True', false => 'False'], 'Status', 'status');
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

    public function toggleStatus(\Illuminate\Http\Request $request, $id)
    {
        $this->crud->hasAccessOrFail('update');

        $data = $request->validate([
            'value' => 'required|boolean',
        ]);

        $branch = \App\Models\Branch::findOrFail($id);
        $branch->status = (bool) $data['value'];
        $branch->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Branch status updated successfully.',
            'value' => $branch->status ? 1 : 0,
        ]);
    }

    public function destroy($id)
    {
        $response = $this->traitDestroy($id);
        app(CourseMatchReferenceService::class)->syncReferenceSource('branches');
        return $response;
    }
}
