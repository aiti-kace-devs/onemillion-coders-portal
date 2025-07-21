<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CourseCertificationRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\FilterHelper;
use App\Helpers\CourseFieldHelpers;
use App\Helpers\WidgetHelper;
use App\Models\Programme;
use App\Helpers\GeneralFieldsAndColumns;
/**
 * Class CourseCertificationCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CourseCertificationCrudController extends CrudController
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
        CRUD::setModel(\App\Models\CourseCertification::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/course-certification');
        CRUD::setEntityNameStrings('course certification', 'course certifications');

        $this->crud->operation('list', function () {
            WidgetHelper::courseCertificationStatisticsWidget();
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
        FilterHelper::addGenericRelationshipColumn('programme', 'Course', 'programme', 'title');
        CRUD::column('type')->type('text');
        FilterHelper::addBooleanColumn('status', 'status');
        $this->addProgrammeFilter('programme_id');
        FilterHelper::addBooleanFilter('status', 'Status');
        FilterHelper::addDateRangeFilter('created_at', 'Created Date');
    }


    protected function setupShowOperation()
    {
        CRUD::column('title')->type('textarea');
        FilterHelper::addGenericRelationshipColumn('programme', 'Course', 'programme', 'title');
        CRUD::column('type')->type('text');
        CRUD::column('description')->type('textarea');
        FilterHelper::addBooleanColumn('status', 'status');
        CRUD::column('created_at');
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(CourseCertificationRequest::class);
        CRUD::addField(field: [
            'name' => 'title',
            'label' => 'Title',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
            'hint' => 'eg. Microsoft PL-300: Power BI Data Analyst Associate'
        ]);

        CRUD::addField([
            'name' => 'type',
            'label' => 'Type',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
            'hint' => 'eg. International Certification'
        ]);

        CRUD::addField([
            'name' => 'programme_id',
            'label' => 'Programme',
            'type' => 'select2',
            'entity' => 'programme',
            'attribute' => 'title',
            'model' => Programme::class,
            'allows_null' => false,
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        CRUD::addField([
            'name' => 'description',
            'label' => 'Description',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
            'hint' => 'eg. Industry-recognized certification that validates your skills and expertise.'
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
