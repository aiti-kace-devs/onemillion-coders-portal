<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\MasterSessionRequest;
use App\Models\MasterSession;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\CrudListHelper;
use App\Helpers\FilterHelper;

/**
 * Class MasterSessionCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class MasterSessionCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(MasterSession::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/master-session');
        CRUD::setEntityNameStrings('master session', 'master sessions');
    }

    protected function setupListOperation(): void
    {
        CrudListHelper::editInDropdown();

        CRUD::column('master_name')->label('Name')->type('text');
        CRUD::column('session_type')->label('Session Type');
        CRUD::column('time')->label('Time');
        CRUD::column('course_type')->label('Course Type');
        FilterHelper::addBooleanColumn('status', 'Status');
        CRUD::column('created_at')->label('Created At');

        FilterHelper::addSelectFilter(
            'session_type',
            'Filter Session Type',
            [
                'Morning'   => 'Morning',
                'Afternoon' => 'Afternoon',
                'Evening'   => 'Evening',
                'Fullday'   => 'Fullday',
                'Online'    => 'Online',
            ],
            'select2_multiple'
        );
        FilterHelper::addBooleanFilter('status', 'Status');
        FilterHelper::addDateRangeFilter('created_at', 'Created At');
    }

    protected function setupShowOperation(): void
    {
        CRUD::column('master_name')->label('Name')->type('text');
        CRUD::column('session_type')->label('Session Type');
        CRUD::column('time')->label('Time');
        CRUD::column('course_type')->label('Course Type');
        FilterHelper::addBooleanColumn('status', 'Status');
        CRUD::column('created_at')->label('Created At');
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(MasterSessionRequest::class);
        $this->masterSessionFields();
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();
    }

    protected function masterSessionFields(): void
    {
        CRUD::field('master_name')
            ->label('Name')
            ->type('text')
            ->attributes(['required' => 'required']);

        CRUD::field('session_type')
            ->label('Session Type')
            ->type('select_from_array')
            ->options([
                'Morning'   => 'Morning',
                'Afternoon' => 'Afternoon',
                'Evening'   => 'Evening',
                'Fullday'   => 'Fullday',
                'Online'    => 'Online',
            ])
            ->allows_null(false)
            ->attributes(['required' => 'required']);

        CRUD::field('time')
            ->label('Time')
            ->type('text')
            ->hint('e.g. 9:00am - 12:00pm')
            ->attributes(['required' => 'required']);

        CRUD::field('course_type')
            ->label('Course Type')
            ->type('text')
            ->hint('e.g. standard, weekend, intensive')
            ->attributes(['required' => 'required']);

        CRUD::field('status')
            ->label('Active')
            ->type('checkbox')
            ->default(true);
    }

    /**
     * Override delete to mark inactive instead of hard-deleting,
     * preserving data integrity and auditability.
     */
    public function destroy($id)
    {
        $session = MasterSession::findOrFail($id);
        $session->status = false;
        $session->save();

        return response()->json(['message' => 'Master session marked as inactive.']);
    }
}
