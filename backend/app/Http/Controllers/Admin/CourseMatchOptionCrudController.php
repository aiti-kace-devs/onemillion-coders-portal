<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CourseMatchOptionRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\CourseFieldHelpers;
use App\Helpers\WidgetHelper;
use App\Helpers\FilterHelper;

/**
 * Class CourseMatchOptionCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CourseMatchOptionCrudController extends CrudController
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
        CRUD::setModel(\App\Models\CourseMatchOption::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/course-match-option');
        CRUD::setEntityNameStrings('course match option', 'course match options');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        WidgetHelper::CourseMatchOptionStatisticsWidget();

        CRUD::column('value');
        CRUD::column('answer');
        CRUD::column('description');
        FilterHelper::addGenericRelationshipColumn('courseMatch', 'Course Match', 'courseMatch', 'question');
        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
        ]);
        FilterHelper::addBooleanFilter('status', 'Status');
        FilterHelper::addDateRangeFilter('created_at', 'Created Date');
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(CourseMatchOptionRequest::class);
        $this->courseMatchOptionsFields();
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

    public function toggleStatus(\Illuminate\Http\Request $request, $id)
    {
        $this->crud->hasAccessOrFail('update');

        $data = $request->validate([
            'value' => 'required|boolean',
        ]);

        $courseMatchOption = \App\Models\CourseMatchOption::findOrFail($id);
        $courseMatchOption->status = (bool) $data['value'];
        $courseMatchOption->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Course match option status updated successfully.',
            'value' => $courseMatchOption->status ? 1 : 0,
        ]);
    }
}
