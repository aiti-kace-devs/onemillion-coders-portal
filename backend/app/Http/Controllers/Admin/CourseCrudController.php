<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CourseRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\CrudPanel\Hooks\Facades\LifecycleHook;
use App\Helpers\GeneralFieldsAndColumns;
use App\Helpers\CourseFieldHelpers;
use App\Helpers\WidgetHelper;
use App\Helpers\FilterHelper;
use App\Models\Course;

/**
 * Class CourseCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CourseCrudController extends CrudController
{
    use GeneralFieldsAndColumns;
    use \App\SearchableCRUD;
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
        CRUD::setModel(\App\Models\Course::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/course');
        CRUD::setEntityNameStrings('course', 'courses');

        $this->setSearchableColumns(['course_name', 'description']);
        $this->setSearchResultAttributes(['id', 'course_name', 'description']);

        // Add permission checks
        LifecycleHook::hookInto(['list:before_setup', 'show:before_setup'], function () {
            $this->crud->addClause('where', function ($query) {
                if (!backpack_user()->can('course.read.all')) {
                    // Add any specific filtering logic here if needed
                    // For now, we'll use the scope from the model
                    $query->myAssignedCourses();
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
        WidgetHelper::courseStatisticsWidget();

        // Check permissions
        if (!backpack_user()->can('course.read.all')) {
            abort(403, 'Unauthorized action.');
        }

        CRUD::column('course_name')->type('textarea');
        // CRUD::column('batch_id')->label('Batch')->linkTo('batch.show');
        CRUD::column('duration');
        // CRUD::column('no_of_days');
        CRUD::column('centre_id')->label('Centre')->linkTo('centre.show');
        FilterHelper::addBooleanColumn('status', 'status');
        // CRUD::column('programme_id')->label('Programme')->linkTo('programme.show');
        // $this->addBatchFilter('batch_id');
        $this->courseFilter('id');
        FilterHelper::addDateRangeFilter('start_date', 'Start Date');
        $this->addOngoingCoursesFilter('Ongoing Courses');
        FilterHelper::addBooleanFilter('status', 'Status');
        FilterHelper::addDateRangeFilter('end_date', 'End Date');
        // FilterHelper::addDateRangeFilter('created_at', 'Created At');
        CRUD::enableExportButtons();
    }

    protected function setupShowOperation()
    {
        // Check permissions
        if (!backpack_user()->can('course.read.all')) {
            abort(403, 'Unauthorized action.');
        }

        CRUD::column('course_name')->type('textarea');
        CRUD::column('duration');
        // CRUD::column('batch_id')->label('Batch')->linkTo('batch.show');
        CRUD::column('no_of_days');
        CRUD::column('start_date');
        CRUD::column('end_date');
        CRUD::column('location');
        CRUD::column('centre_id')->label('Centre')->linkTo('centre.show');
        CRUD::column('programme_id')->label('Programme')->linkTo('programme.show');
        CRUD::column('status')->type('boolean');
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
        // Check permissions
        if (!backpack_user()->can('course.create')) {
            abort(403, 'Unauthorized action.');
        }

        CRUD::setValidation(CourseRequest::class);

        $this->setupCommonFields();

        // Handle batch_id from URL parameter (when adding course from batch edit page)
        $batchId = request()->input('batch_id');
        if ($batchId) {
            $batch = \App\Models\Batch::find($batchId);
            if ($batch) {
                // Pre-fill batch_id
                CRUD::field('batch_id')->value($batchId);
                
                // Pre-fill start_date and end_date from batch
                CRUD::field('start_date')->value($batch->start_date);
                CRUD::field('end_date')->value($batch->end_date);
                
                // Get the branch from the batch's courses if available, or set from first course's centre
                $firstCourse = $batch->courses->first();
                if ($firstCourse && $firstCourse->centre) {
                    CRUD::field('centre_id')->value($firstCourse->centre_id);
                }
            }
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
        if (!backpack_user()->can('course.update.all')) {
            abort(403, 'Unauthorized action.');
        }

        $this->setupCommonFields();
        $entry = $this->crud->getCurrentEntry();

        CRUD::field('centre_id')
            ->default([
                'id' => $entry->centre_id ?? null,
                'text' => $entry->centre->title ?? ''
            ]);

        CRUD::field('duration')->hint('Updating duration may affect existing schedules');
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
        if (!backpack_user()->can('course.delete.all')) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Return courses as JSON for AJAX requests
     */
    public function ajaxList()
    {
        $courses = \App\Models\Course::query()
            ->with('centre')
            ->whereHas('batch', function ($query) {
                $query->where('completed', false)
                    ->where('status', true);
            })
            ->orderBy('course_name')
            ->get()
            ->map(fn (\App\Models\Course $course) => [
                'id' => $course->id,
                // Backward compatible key used by existing JS.
                'course_name' => $course->display_name,
                'display_name' => $course->display_name,
            ])
            ->values();

        return response()->json($courses);
    }
}
