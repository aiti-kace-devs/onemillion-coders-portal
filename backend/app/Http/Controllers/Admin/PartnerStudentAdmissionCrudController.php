<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\PartnerStudentAdmissionRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class PartnerStudentAdmissionCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class PartnerStudentAdmissionCrudController extends CrudController
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
        CRUD::setModel(\App\Models\PartnerStudentAdmission::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/partner-student-admission');
        CRUD::setEntityNameStrings('partner admission', 'partner admissions');

        // Apply partner filter if provided in URL (allows separate menu items)
        if (request()->has('partner')) {
            $this->crud->addClause('whereHas', 'partner', function($query) {
                $query->where('slug', request()->input('partner'));
            });
        }
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('user_id')->label('Student ID');
        CRUD::addColumn([
            'name' => 'user',
            'type' => 'relationship',
            'label' => 'Student Name',
            'attribute' => 'name',
        ]);
        CRUD::column('partner_id')->type('relationship')->label('Partner');
        CRUD::column('programme_id')->type('relationship')->label('Programme');
        CRUD::column('external_user_id')->label('Partner User ID');
        CRUD::column('enrollment_status')->type('text');
        CRUD::column('created_at');

        // Filter by Partner
        $this->crud->addFilter([
            'name'  => 'partner_id',
            'type'  => 'select2',
            'label' => 'Partner'
        ], function() {
            return \App\Models\Partner::all()->pluck('name', 'id')->toArray();
        }, function($value) {
            $this->crud->addClause('where', 'partner_id', $value);
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
        CRUD::setValidation(PartnerStudentAdmissionRequest::class);

        CRUD::field('user_id')->type('text')->label('Student ID (userId)');
        CRUD::field('partner_id')->type('relationship')->label('Partner');
        CRUD::field('programme_id')->type('relationship')->label('Programme');
        CRUD::field('external_user_id')->type('text')->label('Partner Platform User ID');
        CRUD::field('enrollment_status')->type('enum')->options([
            'pending' => 'Pending',
            'admitted' => 'Admitted',
            'failed' => 'Failed',
            'completed' => 'Completed',
        ]);
        CRUD::field('meta')->type('textarea')->hint('JSON metadata');
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
