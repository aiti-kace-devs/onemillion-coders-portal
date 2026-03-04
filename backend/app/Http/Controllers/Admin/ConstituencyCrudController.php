<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FilterHelper;
use App\Models\Branch;
use App\Helpers\WidgetHelper;
use App\Http\Requests\ConstituencyRequest;
use App\Models\Constituency;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ConstituencyCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ConstituencyCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Constituency::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/constituency');
        CRUD::setEntityNameStrings('constituency', 'constituencies');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {

        CRUD::column('title')->label('Title');
        CRUD::column('description')->label('Description');
        CRUD::column('branch_id')->label('Region')->linkTo('branch.show');

        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
            'toggle_url' => 'constituency/{id}/toggle',
            'toggle_success_message' => 'Constituency status updated successfully.',
            'toggle_error_message' => 'Error updating constituency status.',
        ]);
        CRUD::column('created_at')->label('Created At');

        FilterHelper::addSelectFilter(
            'branch_id',
            'Region',
            Branch::query()->orderBy('title')->pluck('title', 'id')->toArray(),
            'select2'
        );

        FilterHelper::addBooleanFilter('status');
        FilterHelper::addDateRangeFilter('created_at', 'Created At');
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
        CRUD::setValidation(ConstituencyRequest::class);
        $this->setupConstituencyFields();
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        CRUD::setValidation(ConstituencyRequest::class);
        $this->setupConstituencyFields();
    }

    /**
     * Define what happens when the Show operation is loaded.
     *
     * @return void
     */
    protected function setupShowOperation()
    {
        CRUD::set('show.setFromDb', false);
        CRUD::set('show.view', 'vendor.backpack.crud.constituency_show');
    }



    protected function setupConstituencyFields(bool $includeCreateCentresField = false): void
    {
        CRUD::addField([
            'name' => 'title',
            'label' => 'Title',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
            'tab' => 'General Info',
        ]);

        CRUD::addField([
            'name' => 'description',
            'label' => 'Description',
            'type' => 'textarea',
            'attributes' => ['rows' => 2],
            'wrapper' => ['class' => 'form-group col-6'],
            'tab' => 'General Info',
        ]);

        CRUD::addField([
            'name' => 'branch_id',
            'label' => 'Select Region',
            'type' => 'select',
            'entity' => 'branch',
            'model' => Branch::class,
            'attribute' => 'title',
            'wrapper' => ['class' => 'form-group col-6'],
            'tab' => 'General Info',
        ]);

        
        CRUD::addField([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'switch',
            'wrapper' => ['class' => 'form-group col-6'],
            'tab' => 'General Info',
        ]);



    }





    public function toggleStatus($id)
    {
        $constituency = Constituency::findOrFail($id);

        $rawValue = request()->input('value');
        if ($rawValue === null) {
            return response()->json([
                'message' => 'Missing status value.',
            ], 422);
        }

        $parsedBoolean = filter_var($rawValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($parsedBoolean === null && !in_array($rawValue, [0, 1, '0', '1'], true)) {
            return response()->json([
                'message' => 'Invalid status value.',
            ], 422);
        }

        $newValue = $parsedBoolean ?? ((int) $rawValue === 1);

        $constituency->status = $newValue;
        $constituency->save();

        return response()->json([
            'message' => 'Constituency status updated successfully.',
            'value' => $constituency->status ? 1 : 0,
        ]);
    }



}
