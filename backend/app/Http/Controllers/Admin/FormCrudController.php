<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\RegistrationFormRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\FilterHelper;
use App\Helpers\StudentFormFieldHelpers;
/**
 * Class FormCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class FormCrudController extends CrudController
{
    use StudentFormFieldHelpers;
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
        CRUD::setModel(\App\Models\Form::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/form');
        CRUD::setEntityNameStrings('form', 'forms');

        $this->setSearchableColumns(['title', 'description']);
        $this->setSearchResultAttributes(['id', 'title', 'description']);

        // Add permission checks
        $this->crud->operation(['list', 'show'], function () {
            $this->crud->addClause('where', function ($query) {
                if (!backpack_user()->can('form.read.all')) {
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
        if (!backpack_user()->can('form.read.all')) {
            abort(403, 'Unauthorized action.');
        }

        CRUD::column('title')->type('textarea');
        FilterHelper::addBooleanColumn('active', 'status');
        CRUD::column('created_at');
        CRUD::enableExportButtons();
    }

    protected function setupShowOperation()
    {
        CRUD::set('show.setFromDb', true);

        CRUD::addButtonFromModelFunction('line', 'preview_form', 'getPreviewButton', 'beginning');
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
        if (!backpack_user()->can('form.create')) {
            abort(403, 'Unauthorized action.');
        }

        CRUD::setValidation(RegistrationFormRequest::class);
        $this->setupCreateRegistrationFormFields();
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
        if (!backpack_user()->can('form.update.all')) {
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
        if (!backpack_user()->can('form.delete.all')) {
            abort(403, 'Unauthorized action.');
        }
    }
}
