<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CrudListHelper;
use App\Helpers\FilterHelper;
use App\Helpers\GeneralFieldsAndColumns;
use App\Http\Requests\AppConfigRequest;
use App\Models\AppConfig;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;

/**
 * Class AppConfigCrudController
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class AppConfigCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use GeneralFieldsAndColumns;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\AppConfig::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/app-config');
        CRUD::setEntityNameStrings('app config', 'app configs');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     *
     * @return void
     */
    protected function setupListOperation()
    {
        CrudListHelper::editInDropdown();

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
     *
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(AppConfigRequest::class);
        CRUD::addField([
            'name' => 'key',
            'label' => 'Key',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        CRUD::addField([
            'name' => 'type',
            'label' => 'Type',
            'type' => 'enum',
            'options' => [
                'string' => 'String',
                'boolean' => 'Boolean',
                'integer' => 'Integer',
                'json' => 'JSON',
                'array' => 'Array',
            ],
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        $this->addAppConfigValueField($this->resolveAppConfigValueFieldType());

        $this->addIsActiveField([true => 'True', false => 'False'], 'Is Cached', 'is_cached');
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     *
     * @return void
     */
    protected function setupUpdateOperation()
    {
        CRUD::setValidation(AppConfigRequest::class);

        CRUD::field('key')
            ->type('text')
            ->attributes(['readonly' => 'readonly']);

        CRUD::addField([
            'name' => 'type',
            'label' => 'Type',
            'type' => 'enum',
            'options' => [
                'string' => 'String',
                'boolean' => 'Boolean',
                'integer' => 'Integer',
                'json' => 'JSON',
                'array' => 'Array',
            ],
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        $this->addAppConfigValueField($this->resolveAppConfigValueFieldType());

        $this->addIsActiveField([true => 'True', false => 'False'], 'Is Cached', 'is_cached');
    }

    /**
     * Type for the Value input: prefers submitted/old input (after validation errors), else stored type on edit.
     */
    protected function resolveAppConfigValueFieldType(): string
    {
        $fromRequest = request()->old('type', request()->input('type'));
        if (is_string($fromRequest) && $fromRequest !== '') {
            return $fromRequest;
        }

        $entry = $this->crud->getCurrentEntry();
        if ($entry && $entry->type) {
            return (string) $entry->type;
        }

        return 'string';
    }

    protected function addAppConfigValueField(string $type): void
    {
        $entry = $this->crud->getCurrentEntry();
        $common = [
            'name' => 'value',
            'label' => 'Value',
            'wrapper' => ['class' => 'form-group col-md-12'],
        ];

        if ($type === 'integer') {
            CRUD::addField(array_merge($common, [
                'type' => 'number',
                'attributes' => ['step' => '1'],
            ]));

            return;
        }

        if ($type === 'boolean') {
            CRUD::addField(array_merge($common, [
                'type' => 'select_from_array',
                'options' => [
                    '0' => 'No / Disabled',
                    '1' => 'Yes / Enabled',
                ],
                'allows_null' => false,
            ]));

            return;
        }

        $field = array_merge($common, [
            'type' => 'textarea',
            'attributes' => ['rows' => $type === 'string' ? 3 : 8],
        ]);

        if ($entry && $entry->key === APPLICATION_REVIEW_IFRAME_URL) {
            $field['hint'] = 'Full URL (https://…) or a path such as /application-review (uses QUIZ_FRONTEND_URL as base).';
        } elseif (in_array($type, ['json', 'array'], true)) {
            $field['hint'] = 'Enter valid JSON for JSON type, or serialized PHP array format for Array (advanced).';
        }

        CRUD::addField($field);
    }

    public function toggleValue(Request $request, $id)
    {
        $config = AppConfig::findOrFail($id);

        if ($config->type !== 'boolean') {
            return response()->json(['status' => 'error', 'message' => 'Not a boolean config'], 400);
        }

        $config->value = $request->input('value');
        $config->save();

        return response()->json(['status' => 'success', 'message' => 'Updated successfully']);
    }
}
