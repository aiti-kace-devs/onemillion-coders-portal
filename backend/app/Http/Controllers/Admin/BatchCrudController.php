<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\BatchRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\CrudPanel\Hooks\Facades\LifecycleHook;
use App\Helpers\WidgetHelper;
use App\Helpers\FilterHelper;
use App\Models\Batch;
use App\Helpers\CourseFieldHelpers;
use App\Helpers\BatchFieldHelpers;

/**
 * Class BatchCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class BatchCrudController extends CrudController
{
    use CourseFieldHelpers;
    use BatchFieldHelpers;
    use \App\SearchableCRUD;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }
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

        // Add permission checks
        LifecycleHook::hookInto(['list:before_setup', 'show:before_setup'], function () {
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
        WidgetHelper::admissionBatchStatisticsWidget();

        // Check permissions
        if (!backpack_user()->can('batch.read.all')) {
            abort(403, 'Unauthorized action.');
        }

        $this->setupCommonBatchListFields();

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

        $this->setupCommonBatchFields();

        CRUD::removeButton('save_and_new');
        CRUD::removeButton('save_and_preview');
        CRUD::removeButton('preview');

        $this->addCoursesManagementSection();
    }

    /**
     * Define what happens when the Update operation is loaded.
     */
    protected function setupUpdateOperation()
    {
        // Check permissions
        if (!backpack_user()->can('batch.update.all')) {
            abort(403, 'Unauthorized action.');
        }

        CRUD::setValidation(BatchRequest::class);

        // Hide extra save buttons - only show "Save and edit this item"
        CRUD::removeButton('save_and_new');
        CRUD::removeButton('save_and_preview');
        CRUD::removeButton('preview');

        $this->addCoursesManagementSection();

        $this->setupCommonBatchFields();
    }




    protected function setupShowOperation()
    {
        // Don't call setupManageStudentShowColumns() - we use custom view instead
        $this->crud->set('show.setFromDb', false);
        
        // Set custom show view
        $this->crud->setShowView('vendor.backpack.crud.manage_student_show');

    }




    /**
     * Add courses management section to the edit page
     */
    protected function addCoursesManagementSection()
    {
        $batch = $this->crud->getCurrentEntry();
        
        if (!$batch) {
            // On create, show a message that courses can be added after saving
            CRUD::addField([
                'name' => 'courses_notice',
                'type' => 'custom_html',
                'value' => '<div class="alert alert-info">
                    <i class="la la-info-circle"></i> 
                    <strong>Assign Courses:</strong> Save this batch first, then you can assign courses on the edit page.
                </div>',
                'tab' => 'Assign Courses',
            ]);
            return;
        }

        CRUD::addField([
            'name' => 'add_course_modal',
            'type' => 'custom_html',
            'value' => $this->getAddCourseModalHtml($batch),
            'tab' => 'Assign Courses',
        ]);

        CRUD::addField([
            'name' => 'course_actions',
            'type' => 'custom_html',
            'value' => $this->getCoursesActionsHtml($batch),
            'tab' => 'Assign Courses',
        ]);
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




    public function store()
    {
        // Check permissions
        if (!backpack_user()->can('batch.create')) {
            abort(403, 'Unauthorized action.');
        }

        $response = $this->traitStore();
        
        // Get the created batch ID
        $batchId = $this->crud->entry->id ?? null;
        
        if ($batchId && $response->redirectUrl) {
            // Change redirect to edit page so user can add courses
            $response->setRedirect(url(backpack_url('batch/' . $batchId . '/edit')));
        }

        return $response;
    }

    public function update()
    {
        // Check permissions
        if (!backpack_user()->can('batch.update.self')) {
            abort(403, 'Unauthorized action.');
        }

        $response = $this->traitUpdate();

        return $response;
    }




}
