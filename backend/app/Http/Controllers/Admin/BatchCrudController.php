<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\BatchRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\WidgetHelper;
use App\Helpers\FilterHelper;
use App\Models\Batch;
use App\Helpers\CourseFieldHelpers;
/**
 * Class BatchCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class BatchCrudController extends CrudController
{
    use CourseFieldHelpers;
    use \App\SearchableCRUD;
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
        CRUD::setModel(\App\Models\Batch::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/batch');
        CRUD::setEntityNameStrings('batch', 'batches');

        $this->setSearchableColumns(['name', 'description']);
        $this->setSearchResultAttributes(['id', 'name', 'description']);

        $this->crud->operation('list', function () {
            WidgetHelper::admissionBatchStatisticsWidget();
        });

        // Add permission checks
        $this->crud->operation(['list', 'show'], function () {
            $this->crud->addClause('where', function ($query) {
                if (!backpack_user()->can('batch.read.all')) {
                    // Add any specific filtering logic here if needed
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
        if (!backpack_user()->can('batch.read.all')) {
            abort(403, 'Unauthorized action.');
        }

        $this->crud->query
            ->select('admission_batches.*')
            ->selectRaw('(SELECT COUNT(ua.id)
                FROM courses c
                LEFT JOIN user_admission ua ON ua.course_id = c.id AND ua.confirmed IS NOT NULL
                WHERE ua.batch_id = admission_batches.id
            ) AS admitted_students_count');

        CRUD::column('title')->type('text');
        // FilterHelper::addGenericRelationshipColumn('course', 'Course', 'course', 'course_name');
        CRUD::column('year');
        CRUD::column('start_date');
        CRUD::column('end_date');

        CRUD::addColumn([
            'name' => 'admitted_students_count',
            'label' => 'Admitted Students',
            'type' => 'closure',
            'function' => function ($entry) {
                $batchId = $entry->id;
                $admittedCount = $entry->admitted_students_count;

                if ($admittedCount > 0) {
                    $url = url("/admin/user?batch_id={$batchId}&confirmed_admission=1");
                    return "<a href='{$url}'>{$admittedCount}</a>";
                }

                return ''; 
            },
            'escaped' => false,
        ]);

        

        // CRUD::column('total_completed_students')->label('Total Completed');
        FilterHelper::addBooleanColumn('completed', 'completed');
        // $this->courseFilter('course_id');
        $this->addOngoingCoursesFilter('Ongoing Batches');
        FilterHelper::addBooleanFilter('completed', 'Filter By Completed');
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
        // Check permissions
        if (!backpack_user()->can('batch.create')) {
            abort(403, 'Unauthorized action.');
        }

        CRUD::setValidation(BatchRequest::class);
        CRUD::addField([
            'name' => 'title',
            'label' => 'Title',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
            'hint' => 'eg. Quarter 1, Batch 1'
        ]);

        CRUD::addField([
            'name' => 'description',
            'label' => 'Description',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
            // 'hint' => 'eg. 8am - 1pm'
        ]);
        // CRUD::addField([
        //     'name' => 'course_id',
        //     'label' => 'Course',
        //     'type' => 'select2',
        //     'entity' => 'course',
        //     'attribute' => 'course_name',
        //     'model' => Course::class,
        //     'allows_null' => false,
        //     'wrapper' => ['class' => 'form-group col-6'],
        // ]);

        CRUD::addField([
            'name' => 'start_date',
            'label' => 'Start Date',
            'type'      => 'date',
            'wrapper' => ['class' => 'form-group col-6'],
            // 'hint' => 'eg. 8am - 1pm'
        ]);

        CRUD::addField([
            'name' => 'end_date',
            'label' => 'End Date',
            'type'      => 'date',
            'wrapper' => ['class' => 'form-group col-6'],
            // 'hint' => 'eg. 8am - 1pm'
        ]);


        $this->addIsActiveField([ true  => 'True', false => 'False'], 'Status', 'status');

        $this->addIsActiveField([ true  => 'True', false => 'False'], 'Completed', 'completed');
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
        if (!backpack_user()->can('batch.update.all')) {
            abort(403, 'Unauthorized action.');
        }

        $this->setupCreateOperation();
    }

    /**
     * Define what happens when the Delete operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-delete
     * @return void
     */
    protected function setupDeleteOperation()
    {
        // Check permissions
        if (!backpack_user()->can('batch.delete.all')) {
            abort(403, 'Unauthorized action.');
        }
    }
}
