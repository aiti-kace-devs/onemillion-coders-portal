<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CourseRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\Course;
use App\Models\Batch;
use App\Models\Programme;
use App\Models\Centre;
use App\Helpers\FilterHelper;
use App\Helpers\CourseFieldHelpers;
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
        CRUD::setModel(Course::class);
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
            'name' => 'course_name',
            'label' => 'Course Name',
            'type' => 'text',
        ]);

        
        FilterHelper::addGenericRelationshipColumn('batch', 'Batch', 'batch', 'title');
        FilterHelper::addGenericRelationshipColumn('centre', 'Centre', 'centre', 'title');   

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
            'name' => 'course_name',
            'label' => 'Course Name',
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'batch_title',
            'label' => 'Batch',
            'type' => 'closure',
            'function_count' => 1,
            'function' => function($entry) {
                $url = url('admin/batch/'.$entry->batch_id.'/show');
                return '<a href="'.$url.'" class="text-primary font-bold">'.$entry->batch->title.'</a>';
            },
            'escaped' => false,
        ]);

        CRUD::addColumn([
            'name' => 'centre_title',
            'label' => 'Centre',
            'type' => 'closure',
            'function_count' => 1,
            'function' => function($entry) {
                $url = url('admin/centre/'.$entry->centre_id.'/show');
                return '<a href="'.$url.'" class="text-primary font-bold">'.$entry->centre->name.'</a>';
            },
            'escaped' => false,
        ]);

        CRUD::addColumn([
            'name' => 'duration',
            'label' => 'Duration',
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

        CRUD::filter('ongoing')
        ->type('simple')
        ->label('Ongoing Batch Courses')
        ->whenActive(function () {
            $this->crud->query->whereHas('batch', function ($query) {
                $query->whereDate('start_date', '<=', now()->toDateString())
                      ->whereDate('end_date', '>=', now()->toDateString());
            });
        });
        
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
        CRUD::setValidation(CourseRequest::class);

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
            'name' => 'programme_id',
            'label' => 'Programme',
            'type' => 'select2',
            'entity' => 'programme',
            'attribute' => 'title',
            'model' => \App\Models\Programme::class,
            'placeholder' => 'Select a programme',
        ]);

        CRUD::addField([
            'name' => 'centre_id',
            'label' => 'Centre',
            'type' => 'select2',
            'entity' => 'centre',
            'attribute' => 'name',
            'model' => \App\Models\Centre::class,
            'placeholder' => 'Select a centre',
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
