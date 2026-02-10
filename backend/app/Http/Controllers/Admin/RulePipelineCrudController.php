<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;

class RulePipelineCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\RuleAssignment::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/rule-pipeline');
        CRUD::setEntityNameStrings('rule pipeline', 'rule pipelines');
    }

    protected function setupListOperation()
    {
        // Eager load relationships
        $this->crud->with(['rule', 'ruleable']);

        // Order by priority
        $this->crud->orderBy('priority', 'asc');

        CRUD::addColumn([
            'name' => 'ruleable_type',
            'label' => 'Type',
            'type' => 'closure',
            'function' => function ($entry) {
                if ($entry->ruleable_type === 'App\Models\Programme') {
                    return 'Programme';
                } elseif ($entry->ruleable_type === 'App\Models\Course') {
                    return 'Course';
                }
                return 'Unknown';
            }
        ]);

        CRUD::addColumn([
            'name' => 'ruleable',
            'label' => 'Programme/Course',
            'type' => 'closure',
            'function' => function ($entry) {
                if (!$entry->ruleable) {
                    return 'N/A';
                }

                if ($entry->ruleable_type === 'App\Models\Programme') {
                    return $entry->ruleable->title ?? 'N/A';
                } elseif ($entry->ruleable_type === 'App\Models\Course') {
                    return $entry->ruleable->course_name ?? 'N/A';
                }
                return 'N/A';
            }
        ]);

        CRUD::addColumn([
            'name' => 'rule',
            'label' => 'Rule',
            'type' => 'closure',
            'function' => function ($entry) {
                return $entry->rule->name ?? 'N/A';
            }
        ]);

        CRUD::column('priority')->type('number');

        CRUD::addColumn([
            'name' => 'value',
            'label' => 'Parameters',
            'type' => 'closure',
            'function' => function ($entry) {
                $value = $entry->value;
                return $value ? json_encode($value, JSON_PRETTY_PRINT) : 'Default';
            }
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'ruleable_type' => 'required|in:App\\Models\\Programme,App\\Models\\Course',
            'ruleable_id' => 'required|integer',
            'rule_id' => 'required|exists:rules,id',
            'priority' => 'required|integer|min:0',
            'value' => 'nullable',
        ]);

        CRUD::addField([
            'name' => 'ruleable_type',
            'label' => 'Assign To',
            'type' => 'select_from_array',
            'options' => [
                'App\\Models\\Programme' => 'Programme',
                'App\\Models\\Course' => 'Course',
            ],
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'programme_select',
            'label' => 'Select Programme',
            'type' => 'select2_from_array',
            'options' => \App\Models\Programme::pluck('title', 'id')->toArray(),
            'allows_null' => true,
            'wrapper' => ['class' => 'form-group col-md-6'],
            'hint' => 'Only shows if "Programme" is selected above',
            'attributes' => [
                'data-depends-on' => 'ruleable_type',
                'data-depends-value' => 'App\\Models\\Programme',
            ],
        ]);

        CRUD::addField([
            'name' => 'course_select',
            'label' => 'Select Course',
            'type' => 'select2_from_array',
            'options' => \App\Models\Course::pluck('course_name', 'id')->toArray(),
            'allows_null' => true,
            'wrapper' => ['class' => 'form-group col-md-6'],
            'hint' => 'Only shows if "Course" is selected above',
            'attributes' => [
                'data-depends-on' => 'ruleable_type',
                'data-depends-value' => 'App\\Models\\Course',
            ],
        ]);

        CRUD::addField([
            'name' => 'ruleable_id',
            'type' => 'hidden',
            'entity' => false,
        ]);

        CRUD::addField([
            'name' => 'rule_id',
            'label' => 'Rule',
            'type' => 'select2',
            'entity' => 'rule',
            'model' => 'App\\Models\\Rule',
            'attribute' => 'name',
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'priority',
            'label' => 'Priority (0 = highest)',
            'type' => 'number',
            'default' => 0,
            'attributes' => ['min' => 0],
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'value',
            'label' => 'Parameters (JSON)',
            'type' => 'textarea',
            'hint' => 'Override default parameters. Leave empty for defaults. Example: {"pass_mark": 70, "gender": "female"}',
        ]);
    }

    public function store()
    {
        // Manually set ruleable_id based on the selected programme or course
        $request = $this->crud->getRequest();

        if ($request->ruleable_type === 'App\\Models\\Programme') {
            $request->merge(['ruleable_id' => $request->programme_select]);
        } elseif ($request->ruleable_type === 'App\\Models\\Course') {
            $request->merge(['ruleable_id' => $request->course_select]);
        }

        // Convert value to array if it's JSON string
        if ($request->has('value') && is_string($request->value) && !empty($request->value)) {
            try {
                $decoded = json_decode($request->value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $request->merge(['value' => $decoded]);
                }
            } catch (\Exception $e) {
                // Keep as is if not valid JSON
            }
        }

        $this->crud->setRequest($request);
        $this->crud->unsetValidation();

        return $this->traitStore();
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();

        $entry = $this->crud->getCurrentEntry();
        if ($entry && is_array($entry->value)) {
            CRUD::modifyField('value', [
                'value' => json_encode($entry->value, JSON_PRETTY_PRINT),
            ]);
        }
    }

    public function update()
    {
        // Manually set ruleable_id based on the selected programme or course
        $request = $this->crud->getRequest();

        if ($request->ruleable_type === 'App\\Models\\Programme') {
            $request->merge(['ruleable_id' => $request->programme_select]);
        } elseif ($request->ruleable_type === 'App\\Models\\Course') {
            $request->merge(['ruleable_id' => $request->course_select]);
        }

        // Convert value to array if it's JSON string
        if ($request->has('value') && is_string($request->value) && !empty($request->value)) {
            try {
                $decoded = json_decode($request->value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $request->merge(['value' => $decoded]);
                }
            } catch (\Exception $e) {
                // Keep as is if not valid JSON
            }
        }

        $this->crud->setRequest($request);
        $this->crud->unsetValidation();

        return $this->traitUpdate();
    }
}
