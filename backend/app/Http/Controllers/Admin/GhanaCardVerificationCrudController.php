<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\GhanaCardVerificationRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class GhanaCardVerificationCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class GhanaCardVerificationCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\GhanaCardVerification::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/ghana-card-verification');
        CRUD::setEntityNameStrings('ghana card verification', 'ghana card verifications');

        $this->crud->denyAccess(['create', 'update']);

        $this->crud->addButtonFromView('line', 'reset_verification_block', 'reset_verification_block', 'end');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('user_id')->type('select')->model('App\Models\User')->attribute('name')->label('User');
        CRUD::column('pin_number')->label('Ghana Card PIN');
        CRUD::column('code')->label('Response Code');
        CRUD::column('success')->type('boolean');
        CRUD::column('verified')->type('boolean');
        CRUD::column('request_timestamp')->type('datetime');
        CRUD::column('created_at')->label('Attempted At');

        $this->crud->addFilter([
            'name'  => 'code',
            'type'  => 'dropdown',
            'label' => 'Response Code'
        ], [
            '00' => '00 - Success',
            '01' => '01 - Unsuccessful',
            '02' => '02 - Invalid Data',
            '03' => '03 - NIA Watchlist',
            '04' => '04 - Server Error',
            '99' => '99 - Unknown Error'
        ], function ($value) {
            $this->crud->addClause('where', 'code', $value);
        });
    }

    /**
     * Define what happens when the Show operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-show
     * @return void
     */
    protected function setupShowOperation()
    {
        $this->setupListOperation();

        CRUD::column('transaction_guid');
        CRUD::column('response_timestamp')->type('datetime');
        CRUD::column('status_message');
        CRUD::column('person_data')->type('json');
    }

    /**
     * Reset the verification block for the associated user.
     */
    public function resetBlock($user_id)
    {
        $user = \App\Models\User::findOrFail($user_id);
        $user->update(['is_verification_blocked' => false]);

        return response()->json([
            'success' => true,
            'message' => 'User verification block has been reset.',
        ]);
    }
}
