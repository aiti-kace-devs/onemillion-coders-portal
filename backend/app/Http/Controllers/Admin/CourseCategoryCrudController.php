<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CourseCategoryRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\GeneralFieldsAndColumns;
use App\Helpers\FilterHelper;
use App\Helpers\WidgetHelper;
use App\Helpers\CrudListHelper;

/**
 * Class CourseCategoryCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CourseCategoryCrudController extends CrudController
{
    use GeneralFieldsAndColumns;
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
        CRUD::setModel(\App\Models\CourseCategory::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/course-category');
        CRUD::setEntityNameStrings('course category', 'course categories');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CrudListHelper::editInDropdown();
        WidgetHelper::courseCategoryStatisticsWidget();

        CRUD::column('title')->type('textarea');
        CRUD::column('description')->type('textarea');
        FilterHelper::addBooleanColumn('status', 'status');
        CRUD::column('created_at');
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
        CRUD::setValidation(CourseCategoryRequest::class);
        CRUD::addField(field: [
            'name' => 'title',
            'label' => 'Title',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-5'],
        ]);


        CRUD::addField([
            'name' => 'icon',
            'label' => 'Icon',
            'type'      => 'icon_picker',
            'wrapper' => ['class' => 'form-group col-2'],
        ]);

        CRUD::addField([
            'name' => 'description',
            'label' => 'Description',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-5'],
        ]);

        $this->addIsActiveField([true  => 'True', false => 'False'], 'Status', 'status');
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
