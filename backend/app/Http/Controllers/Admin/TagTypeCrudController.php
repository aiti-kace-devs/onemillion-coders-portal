<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\CrudListHelper;

class TagTypeCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\TagType::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/tag-type');
        CRUD::setEntityNameStrings('tag type', 'tag types');
    }

    protected function setupListOperation()
    {
        CrudListHelper::editInDropdown();
        CRUD::column('name');

        CRUD::addColumn([
            'name' => 'target_models',
            'label' => 'Target Models',
            'type' => 'closure',
            'function' => function ($entry) {
                if (!$entry->target_models)
                    return '-';
                $models = is_array($entry->target_models) ? $entry->target_models : json_decode($entry->target_models, true);

                $formatted = array_map(function ($model) {
                    return \App\Models\TagType::AVAILABLE_TARGET_MODELS[$model] ?? str_replace('App\\Models\\', '', $model);
                }, $models);

                return implode(', ', $formatted);
            }
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'name' => 'required|min:2|max:255|unique:tag_types,name',
            'target_models' => 'nullable|array',
        ]);

        CRUD::field('name')->type('text');

        $options = \App\Models\TagType::AVAILABLE_TARGET_MODELS;

        CRUD::addField([
            'name' => 'target_models',
            'label' => 'Target Models',
            'type' => 'select2_from_array',
            'options' => $options,
            'allows_null' => true,
            'allows_multiple' => true,
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();

        CRUD::setValidation([
            'name' => 'required|min:2|max:255|unique:tag_types,name,' . CRUD::getCurrentEntryId() . ',id',
            'target_models' => 'nullable|array',
        ]);
    }
}
