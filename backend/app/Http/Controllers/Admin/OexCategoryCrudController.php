<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\OexCategoryRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\FilterHelper;
use App\Helpers\WidgetHelper;
use App\Helpers\GeneralFieldsAndColumns;
/**
 * Class OexCategoryCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class OexCategoryCrudController extends CrudController
{
    use GeneralFieldsAndColumns;
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
        CRUD::setModel(\App\Models\OexCategory::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/category');
        CRUD::setEntityNameStrings('category', 'categories');

        $this->crud->operation('list', function () {
            WidgetHelper::categorytatisticsWidget();
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
        CRUD::column('name')->type('text');
        FilterHelper::addBooleanColumn('status', 'status');
        CRUD::column('created_at');
        FilterHelper::addBooleanFilter('status', 'Status');
        FilterHelper::addDateRangeFilter('created_at', 'Created At');
        CRUD::enableExportButtons();
    }

        protected function setupShowOperation()
    {
          $this->setupListOperation();
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(OexCategoryRequest::class);
        CRUD::addField([
            'name' => 'name',
            'label' => 'Name',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        $this->addIsActiveField([ true  => 'True', false => 'False'], 'Status', 'status');
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
}
