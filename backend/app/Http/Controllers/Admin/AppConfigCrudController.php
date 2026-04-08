<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\AppConfigRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\AppConfig;
use App\Helpers\GeneralFieldsAndColumns;
use App\Helpers\CrudListHelper;
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
        CrudListHelper::editInDropdown();

        CRUD::orderBy('key', 'asc');

        $this->crud->addFilter(
            [
                'name' => 'key',
                'type' => 'text',
                'label' => 'Config key',
            ],
            false,
            function ($value) {
                if ($value === '' || $value === null) {
                    return;
                }
                CRUD::addClause('where', 'key', 'like', '%'.addcslashes((string) $value, '%_\\').'%');
            }
        );

        CRUD::column('key')->type('textarea')->label('Name');
        CRUD::column('type')->type('text')->label('Type');
        CRUD::addColumn([
            'name' => 'value',
            'label' => 'Value',
            'type' => 'view',
            'view' => 'admin.app_config.value_column',
        ]);
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
            'type' => 'text',
            'attributes' => ['spellcheck' => 'false'],
            'wrapper' => ['class' => 'form-group col-6'],
            'hint' => 'For type Integer, enter digits only. For String (e.g. partner slugs), any normalized slug.',
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

    public function toggleValue(Request $request, $id)
    {
        $config = AppConfig::findOrFail($id);

        if($config->type !== 'boolean') {
            return response()->json(['status' => 'error', 'message' => 'Not a boolean config'], 400);
        }

        $config->value = $request->input('value');
        $config->save();

        return response()->json(['status' => 'success', 'message' => 'Updated successfully']);
    }
}
