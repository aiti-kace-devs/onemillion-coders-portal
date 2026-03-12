<?php

namespace App\Http\Controllers\Admin;

use App\Models\Rule;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
            'name' => 'is_active',
            'label' => 'Active',
            'type' => 'boolean',
        ]);

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
            // rule_parameters field posts an array of [key,value] rows
            'value' => 'nullable|array',
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
            'name' => 'is_active',
            'label' => 'Is Active',
            'type' => 'switch',
            'default' => 1,
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name'  => 'value',
            'label' => 'Rule Parameters',
            'type'  => 'rule_parameters',
            'hint'  => <<<HTML
Overrides the rule's default parameters for this Programme/Course.<br>
<strong>Common keys:</strong>
<ul class="mb-0">
  <li><strong>PassMark</strong>: <code>pass_mark</code></li>
  <li><strong>CompletedExam</strong>: <code>require_completion</code>, <code>require_submission</code></li>
  <li><strong>AppliedBefore</strong>: <code>before_date</code>, <code>priority</code></li>
  <li><strong>SortByDate</strong>: <code>direction</code></li>
  <li><strong>GenderQuota</strong>: <code>gender</code>, <code>min_count</code></li>
  <li><strong>AgeRange</strong>: <code>min_age</code>, <code>max_age</code></li>
  <li><strong>EducationalLevel</strong>: <code>hierarchy</code>, <code>min_level</code></li>
</ul>
HTML,
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

        // Normalize value rows into associative array
        if (is_array($request->value ?? null)) {
            $request->merge([
                'value' => $this->normalizeParametersArray($request->value),
            ]);
        }

        $this->crud->setRequest($request);

        return DB::transaction(function () use ($request) {
            // Priority reordering logic
            $priority = $request->priority;
            $ruleableType = $request->ruleable_type;
            $ruleableId = $request->ruleable_id;

            if ($priority !== null) {
                \App\Models\RuleAssignment::where('ruleable_type', $ruleableType)
                    ->where('ruleable_id', $ruleableId)
                    ->where('priority', '>=', $priority)
                    ->increment('priority');
            }

            $this->crud->unsetValidation();
            return $this->traitStore();
        });
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
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

        // Normalize value rows into associative array
        if (is_array($request->value ?? null)) {
            $request->merge([
                'value' => $this->normalizeParametersArray($request->value),
            ]);
        }

        $this->crud->setRequest($request);

        return DB::transaction(function () use ($request) {
            // Priority reordering logic (only if shifted)
            $entry = $this->crud->getCurrentEntry();
            if ($entry && $request->priority != $entry->priority) {
                $priority = $request->priority;
                $ruleableType = $request->ruleable_type;
                $ruleableId = $request->ruleable_id;

                \App\Models\RuleAssignment::where('ruleable_type', $ruleableType)
                    ->where('ruleable_id', $ruleableId)
                    ->where('id', '!=', $entry->id)
                    ->where('priority', '>=', $priority)
                    ->increment('priority');
            }

            $this->crud->unsetValidation();
            return $this->traitUpdate();
        });
    }

    /**
     * Turn an array of [ [key=>..., value=>...], ... ] into an associative array.
     */
    protected function normalizeParametersArray(array $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            $key = isset($row['key']) ? trim((string) $row['key']) : '';
            if ($key === '') {
                continue;
            }

            $value = $row['value'] ?? null;

            // Best-effort type casting for simple scalars
            if (is_string($value)) {
                $trimmed = trim($value);
                if ($trimmed === 'true' || $trimmed === 'false') {
                    $value = $trimmed === 'true';
                } elseif (is_numeric($trimmed)) {
                    $value = $trimmed + 0;
                }
            }

            $result[$key] = $value;
        }

        return $result;
    }
    /**
     * Get default parameters for a rule (AJAX)
     */
    public function getRuleParameters(Request $request)
    {
        $validated = $request->validate([
            'rule_id' => 'nullable|exists:rules,id',
            'rule_class_path' => 'nullable|string',
        ]);

        $parameters = [];

        if ($request->rule_id) {
            $rule = \App\Models\Rule::find($request->rule_id);
            $parameters = $rule ? $rule->default_parameters : [];
        } elseif ($request->rule_class_path) {
            // Try to find a rule with this class path to get its defaults
            $rule = \App\Models\Rule::where('rule_class_path', $request->rule_class_path)->first();
            if ($rule) {
                $parameters = $rule->default_parameters;
            } else {
                // If not in DB, we could try instantiating the class if it exists and has a default_parameters property/method
                // But for now, returning empty is safe.
                $parameters = [];
            }
        }

        return response()->json([
            'success' => true,
            'parameters' => $parameters ?? [],
        ]);
    }
}
