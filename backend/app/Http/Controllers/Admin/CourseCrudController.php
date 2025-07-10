<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CourseRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
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

        $this->crud->operation('list', function () {
            WidgetHelper::courseStatisticsWidget();
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
        CRUD::column('course_name')->type('textarea');
        CRUD::column('duration');
        CRUD::column('no_of_days');
        CRUD::column('centre_id')->label('Centre')->linkTo('centre.show');
        FilterHelper::addBooleanColumn('status', 'status');
        // CRUD::column('programme_id')->label('Programme')->linkTo('programme.show');
        $this->courseFilter('id');
        $this->addOngoingCoursesFilter();
        FilterHelper::addBooleanFilter('status', 'Status');
        FilterHelper::addDateRangeFilter('created_at', 'Created At');
        CRUD::enableExportButtons();
    }



    protected function setupShowOperation()
    {
        CRUD::column('course_name')->type('textarea');
        CRUD::column('duration');
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
        CRUD::setValidation(CourseRequest::class);
        
        $this->setupCommonFields();
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
protected function setupUpdateOperation()
{
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
     * Return courses as JSON for AJAX requests
     */
    public function ajaxList()
    {
        $courses = \App\Models\Course::select('id', 'course_name')->get();
        return response()->json($courses);
    }
}
