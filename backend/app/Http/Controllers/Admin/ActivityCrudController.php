<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Spatie\Activitylog\Models\Activity;

/**
 * Class ActivityCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ActivityCrudController extends CrudController
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
        CRUD::setModel(\App\Models\ActivityLog::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/activity');
        CRUD::setEntityNameStrings('activity log', 'activity logs');

        // Prevent creating and updating activity logs manually
        $this->crud->denyAccess(['create', 'update', 'delete']);
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('log_name')->label('Log Name');
        CRUD::column('description')->label('Description')->limit(60);
        CRUD::column('subject_id')->label('Subject')->type('closure')->function(function ($entry) {
            if ($entry->subject) {
                $name = $entry->subject->name ?? $entry->subject->title ?? $entry->subject->description ?? $entry->subject_id;
                return class_basename($entry->subject_type) . ': ' . $name;
            }
            return $entry->subject_id;
        });
        CRUD::column('causer_id')->label('Performed By')->type('closure')->function(function ($entry) {
            if ($entry->causer) {
                $name = $entry->causer->name ?? $entry->causer->title ?? $entry->causer->description ?? $entry->causer_id;
                return $name;
            }
            return $entry->causer_id;
        });
        CRUD::column('created_at')->label('Date');
        $this->crud->addFilter(
            [
                'name' => 'log_name',
                'type' => 'dropdown',
                'label' => 'Log Name'
            ],
            Activity::select('log_name')->distinct()->get()->pluck('log_name', 'log_name')->toArray(),
            function ($value) {
                $this->crud->addClause('where', 'log_name', $value);
            }
        );

        $this->crud->addFilter(
            [
                'name' => 'causer_type',
                'type' => 'dropdown',
                'label' => 'Causer Type'
            ],
            Activity::select('causer_type')->whereNotNull('causer_type')->distinct()->get()->pluck('causer_type', 'causer_type')->toArray(),
            function ($value) {
                $this->crud->addClause('where', 'causer_type', $value);
            }
        );

        $this->crud->addFilter(
            [
                'name' => 'subject_type',
                'type' => 'dropdown',
                'label' => 'Subject Type'
            ],
            Activity::select('subject_type')->whereNotNull('subject_type')->distinct()->get()->pluck('subject_type', 'subject_type')->toArray(),
            function ($value) {
                $this->crud->addClause('where', 'subject_type', $value);
            }
        );

        $this->crud->addFilter(
            [
                'type' => 'date_range',
                'name' => 'from_to',
                'label' => 'Date range'
            ],
            false,
            function ($value) { // if the filter is active, apply these constraints
                $dates = json_decode($value);
                $this->crud->addClause('where', 'created_at', '>=', $dates->from);
                $this->crud->addClause('where', 'created_at', '<=', $dates->to . ' 23:59:59');
            }
        );
    }

    /**
     * Define what happens when the Show operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-show
     * @return void
     */
    protected function setupShowOperation()
    {
        $this->setupListOperation();
        CRUD::column('properties')->label('Properties')->type('json');
    }
}
