<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CourseSessionRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\WidgetHelper;
use App\Helpers\FilterHelper;
use App\Models\Course;
use App\Helpers\CourseFieldHelpers;

/**
 * Class CourseSessionCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CourseSessionCrudController extends CrudController
{
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
        CRUD::setModel(\App\Models\CourseSession::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/course-session');
        CRUD::setEntityNameStrings('course session', 'course sessions');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        WidgetHelper::courseSessionStatisticsWidget();

        CRUD::column('name')->type('textarea');
        CRUD::column('limit');
        CRUD::column('course_time');
        // FilterHelper::addGenericRelationshipColumn('course', 'Course', 'course', 'course_name');
        CRUD::column('session');
        CRUD::column('created_at');
        // FilterHelper::addBooleanColumn('status', 'status');
        $this->courseFilter('course_id');
        FilterHelper::addSelectFilter(
            'session',
            'Filter Session',
            [
                'Morning' => 'Morning',
                'Afternoon' => 'Afternoon',
                'Evening' => 'Evening',
                'Fullday' => 'Fullday',
            ],
            'select2_multiple'
        );
        $this->upcomingCourseSessionsFilter();
        // FilterHelper::addBooleanFilter('status', 'Status');
        FilterHelper::addDateRangeFilter('created_at', 'Created At');
        CRUD::enableExportButtons();
    }


    protected function setupShowOperation()
    {
        CRUD::column('name')->type('textarea');
        CRUD::column('limit');
        CRUD::column('course_time');
        FilterHelper::addGenericRelationshipColumn('course', 'Course', 'course', 'course_name');
        CRUD::column('session');
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
        CRUD::setValidation(CourseSessionRequest::class);
        $this->courseSessionFields();
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

    /**
     * Return course sessions as JSON for AJAX requests
     */
    public function ajaxList()
    {
        $courseId = request()->get('course_id');
        $sessions = \App\Models\CourseSession::select('id', 'name', 'course_id')
            ->when($courseId, function ($query) use ($courseId) {
                return $query->where('course_id', $courseId);
            })
            ->get();
        return response()->json($sessions);
    }
}
