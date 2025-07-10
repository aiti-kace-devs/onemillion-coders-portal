<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ProgrammeRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\GeneralFieldsAndColumns;
use App\Helpers\CourseFieldHelpers;
use App\Helpers\WidgetHelper;
use App\Helpers\FilterHelper;
/**
 * Class ProgrammeCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ProgrammeCrudController extends CrudController
{
    use GeneralFieldsAndColumns;
    use CourseFieldHelpers;
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
        CRUD::setModel(\App\Models\Programme::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/programme');
        CRUD::setEntityNameStrings('programme', 'programmes');

        $this->crud->operation('list', function () {
            WidgetHelper::programmeStatisticsWidget();
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
        CRUD::column('title')->type('textarea');
        CRUD::column('duration');
        CRUD::column('start_date');
        CRUD::column('end_date');
        FilterHelper::addBooleanColumn('status', 'status');
        FilterHelper::addBooleanFilter('status', 'Status');
        FilterHelper::addDateRangeFilter('created_at', 'Created At');
        CRUD::enableExportButtons();
    }


    protected function setupShowOperation()
    {
        CRUD::column('title')->type('textarea');
        CRUD::column('duration');
        CRUD::column('start_date');
        CRUD::column('end_date');
        CRUD::column('status')->type('boolean');
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(ProgrammeRequest::class);
                CRUD::addField([
            'name' => 'title',
            'label' => 'Title',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        CRUD::addField([
    'name' => 'duration',
    'label' => 'Duration',
    'type' => 'select_from_array',
    'options' => [
        '1 Week' => '1 Week',
        '2 Week' => '2 Weeks',
        '3 Weeks' => '3 Weeks',
        '4 Weeks' => '4 Weeks',
        '1 Month' => '1 Month',
        '2 Months' => '2 Months',
        '3 Months' => '3 Months',
        '4 Months' => '4 Months',
    ],
    'wrapper' => ['class' => 'form-group col-6'],
]);

        CRUD::addField([
            'name' => 'start_date',
            'label' => 'Start Date',
            'type'      => 'date',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        CRUD::addField([
            'name' => 'end_date',
            'label' => 'End Date',
            'type'      => 'date',
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
