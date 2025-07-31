<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\OexExamMasterRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\UserFieldHelpers;
use App\Helpers\WidgetHelper;
use App\Helpers\FilterHelper;
use App\Models\OexCategory;
use App\Helpers\CourseFieldHelpers;
/**
 * Class OexExamMasterCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class OexExamMasterCrudController extends CrudController
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
        CRUD::setModel(\App\Models\OexExamMaster::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/manage-exam');
        CRUD::setEntityNameStrings('manage exam', 'manage exams');

        $this->crud->operation('list', function () {
            WidgetHelper::manageExamStatisticsWidget();
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
        CRUD::column('title');
        FilterHelper::addCategoryColumn();
        CRUD::column('passmark');
        CRUD::column('exam_date');
        CRUD::column('exam_duration');
        FilterHelper::addBooleanColumn('status', 'status');
        CRUD::addColumn([
            'name' => 'question_link',
            'label' => 'Number of Questions',
            'type' => 'view',
            'view' => 'vendor.backpack.crud.columns.count_link',
            'count_field' => 'questions_count',
            'route' => 'question-master',
            'query_param' => 'exam_id',
        ]);
        FilterHelper::addOngoingExamsFilter('Ongoing Exams');
        FilterHelper::addBooleanFilter('status', 'Status');
        FilterHelper::addDateRangeFilter('created_at', 'Created At');
        $this->crud->addButtonFromView('line', 'custom_action', 'custom_action', 'end');

        CRUD::enableExportButtons();
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();
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
        CRUD::setValidation(OexExamMasterRequest::class);

        CRUD::addField([
            'name' => 'title',
            'label' => 'Title',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);


        CRUD::addField([
    'name' => 'category',
    'label' => 'Category',
    'type' => 'select2',
    'entity' => 'categoryRelation',
    'attribute' => 'name',
    'model' => OexCategory::class,
    'allows_null' => false,
    'wrapper' => ['class' => 'form-group col-6'],
]);

        CRUD::addField([
            'name' => 'exam_duration',
            'label' => 'Exam Duration',
            'type'      => 'number',
            'wrapper' => ['class' => 'form-group col-6'],
            'hint' => 'eg. 30'
        ]);


                CRUD::addField([
            'name' => 'passmark',
            'label' => 'Passmark',
            'type'      => 'number',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        $this->addIsActiveField([ true  => 'True', false => 'False'], 'Status', 'status');


        CRUD::addField([
            'name' => 'exam_date',
            'label' => 'Exam Date',
            'type'      => 'date',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

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
