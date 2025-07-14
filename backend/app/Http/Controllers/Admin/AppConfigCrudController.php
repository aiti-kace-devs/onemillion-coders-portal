<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\AppConfigRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\GeneralFieldsAndColumns;
use App\Helpers\FilterHelper;
/**
 * Class AppConfigCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class AppConfigCrudController extends CrudController
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
        CRUD::setModel(\App\Models\AppConfig::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/app-config');
        CRUD::setEntityNameStrings('app config', 'app configs');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('key')->type('textarea')->label('Name');
        CRUD::column('type')->type('text')->label('Type');
        CRUD::column('value')->label('Value');
        FilterHelper::addBooleanColumn('is_cached', 'Is Cached');
        CRUD::column('created_at');
        FilterHelper::addBooleanFilter('is_cached');
        FilterHelper::addDateRangeFilter('created_at', 'Created At');
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(AppConfigRequest::class);
        CRUD::addField([
            'name' => 'key',
            'label' => 'Key',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        CRUD::addField([
            'name' => 'type',
            'label' => 'Type',
            'type'      => 'enum',
            'options' => [
                'string' => 'String',
                'boolean' => 'Boolean',
                'integer' => 'Integer',
            ],
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        CRUD::addField([
            'name' => 'value',
            'label' => 'Value',
            'type'      => 'number',
            // 'attributes' => ['readonly' => 'readonly'],
            'wrapper' => ['class' => 'form-group col-6'],
        ]);
        $this->addIsActiveField([ true  => 'True', false => 'False'], 'Is Cached', 'is_cached');
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        CRUD::field('key')
            ->type('text')
            ->attributes(['readonly' => 'readonly']);
        $this->setupCreateOperation();
    }
}
