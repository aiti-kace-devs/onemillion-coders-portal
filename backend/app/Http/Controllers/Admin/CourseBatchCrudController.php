<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CourseBatchRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\Course;
use App\Models\Batch;
use App\Models\UserAdmission;
use App\Models\Attendance;
use Illuminate\Support\Facades\View;

/**
 * Class CourseBatchCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CourseBatchCrudController extends CrudController
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
        CRUD::setModel(\App\Models\CourseBatch::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/course-batch');
        CRUD::setEntityNameStrings('Manage Course Batches', 'Manage Course Batches');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->setupFilters();

        CRUD::addColumn([
            'name' => 'course.course_name',
            'label' => 'Course',
            'type' => 'text',
            'attribute' => 'course_name',
        ]);

        CRUD::addColumn([
            'name' => 'batch.title',
            'label' => 'Batch',
            'type' => 'text',
            'attribute' => 'title',
        ]);

        CRUD::addColumn([
            'name' => 'duration',
            'label' => 'Duration',
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'start_date',
            'label' => 'Start Date',
            'type' => 'date',
        ]);

        CRUD::addColumn([
            'name' => 'end_date',
            'label' => 'End Date',
            'type' => 'date',
        ]);
    }

    /**
     * Define what happens when the Show operation is loaded.
     * 
     * @return void
     */
    protected function setupShowOperation()
    {
        CRUD::set('show.setFromDb', false);
        
        // Use custom show view
        CRUD::set('show.view', 'vendor.backpack.crud.course_batch_show');
        
        // Basic info columns
        CRUD::addColumn([
            'name' => 'course.course_name',
            'label' => 'Course',
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'batch.title',
            'label' => 'Batch',
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'duration',
            'label' => 'Duration',
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'start_date',
            'label' => 'Start Date',
            'type' => 'date',
        ]);

        CRUD::addColumn([
            'name' => 'end_date',
            'label' => 'End Date',
            'type' => 'date',
        ]);
    }

    /**
     * Add filters to the list operation.
     */
    protected function setupFilters()
    {
        // Batch filter
        $batches = Batch::all()->pluck('title', 'id')->toArray();
        CRUD::addFilter([
            'name' => 'batch_id',
            'type' => 'select2',
            'label' => 'Batch',
            'placeholder' => 'Select a batch',
        ], function () use ($batches) {
            return $batches;
        }, function ($value) {
            if ($value) {
                $this->crud->addClause('where', 'batch_id', $value);
            }
        });

        // Course filter - handle array of course_ids from URL
        $courses = Course::all()->pluck('course_name', 'id')->toArray();
        CRUD::addFilter([
            'name' => 'course_id',
            'type' => 'select2_multiple',
            'label' => 'Course',
            'placeholder' => 'Select courses',
        ], function () use ($courses) {
            return $courses;
        }, function ($value) {
            if ($value) {
                $decoded = is_array($value) ? $value : json_decode($value, true);
                if (is_array($decoded) && count($decoded) > 0) {
                    $this->crud->addClause('whereIn', 'course_id', $decoded);
                } elseif ($decoded) {
                    $this->crud->addClause('where', 'course_id', $decoded);
                }
            }
        });
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(CourseBatchRequest::class);

        CRUD::addField([
            'name' => 'course_id',
            'label' => 'Course',
            'type' => 'select2',
            'entity' => 'course',
            'attribute' => 'course_name',
            'model' => \App\Models\Course::class,
            'placeholder' => 'Select a course',
        ]);

        CRUD::addField([
            'name' => 'batch_id',
            'label' => 'Batch',
            'type' => 'select2',
            'entity' => 'batch',
            'attribute' => 'title',
            'model' => \App\Models\Batch::class,
            'placeholder' => 'Select a batch',
        ]);

        CRUD::addField([
            'name' => 'duration',
            'label' => 'Duration',
            'type' => 'text',
            'hint' => 'e.g., 4 Weeks, 2 Months',
        ]);

        CRUD::addField([
            'name' => 'start_date',
            'label' => 'Start Date',
            'type' => 'date',
        ]);

        CRUD::addField([
            'name' => 'end_date',
            'label' => 'End Date',
            'type' => 'date',
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
