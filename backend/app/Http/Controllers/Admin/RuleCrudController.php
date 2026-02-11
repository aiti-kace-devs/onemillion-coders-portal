<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class RuleCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Rule::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/admission-rule');
        CRUD::setEntityNameStrings('admission rule', 'admission rules');
    }

    protected function setupListOperation()
    {
        CRUD::column('name')->type('text');
        CRUD::column('description')->type('text')->limit(100);
        CRUD::column('rule_class_path')->type('text')->label('Class Path');

        CRUD::addColumn([
            'name' => 'is_active',
            'label' => 'Active',
            'type' => 'boolean',
            'options' => [0 => 'Inactive', 1 => 'Active']
        ]);

        CRUD::column('created_at');

        CRUD::enableExportButtons();
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'name' => 'required|string|max:255',
            'rule_class_path' => 'required|string|max:255',
            'description' => 'nullable|string',
            'default_parameters' => 'nullable|array',
        ]);

        CRUD::addField([
            'name' => 'name',
            'label' => 'Rule Name',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'rule_class_path',
            'label' => 'Rule Class',
            'type' => 'select2_from_array',
            'options' => $this->getAvailableRules(),
            'allows_null' => false,
            'wrapper' => ['class' => 'form-group col-md-6'],
            'hint' => 'Select from predefined admission rules',
        ]);

        CRUD::addField([
            'name' => 'description',
            'label' => 'Description',
            'type' => 'textarea',
            'attributes' => ['rows' => 3],
        ]);

        CRUD::addField([
            'name' => 'default_parameters',
            'label' => 'Default Parameters',
            'type' => 'rule_parameters',
            'hint' => 'Define the default keys and values for this rule.',
        ]);

        CRUD::addField([
            'name' => 'is_active',
            'label' => 'Active',
            'type' => 'switch',
            'default' => true,
        ]);
    }

    /**
     * Get list of available admission rule classes
     */
    protected function getAvailableRules()
    {
        return [
            'App\\Services\\AdmissionRules\\PassMark' => 'Pass Mark (Filter by minimum exam score)',
            'App\\Services\\AdmissionRules\\CompletedExam' => 'Completed Exam (Ensure exam completion)',
            'App\\Services\\AdmissionRules\\AppliedBefore' => 'Applied Before (Filter/prioritize by date)',
            'App\\Services\\AdmissionRules\\SortByDate' => 'Sort By Date (First-come, first-served)',
            'App\\Services\\AdmissionRules\\GenderQuota' => 'Gender Quota (Ensure gender representation)',
            'App\\Services\\AdmissionRules\\AgeRange' => 'Age Range (Filter by age)',
            'App\\Services\\AdmissionRules\\EducationalLevel' => 'Educational Level (Sort by education hierarchy)',
        ];
    }

    public function store()
    {
        $request = $this->crud->getRequest();
        if ($request->has('default_parameters') && is_string($request->default_parameters)) {
            $request->merge(['default_parameters' => json_decode($request->default_parameters, true)]);
        }
        $this->crud->setRequest($request);
        return $this->traitStore();
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function update()
    {
        $request = $this->crud->getRequest();
        if ($request->has('default_parameters') && is_string($request->default_parameters)) {
            $request->merge(['default_parameters' => json_decode($request->default_parameters, true)]);
        }
        $this->crud->setRequest($request);
        return $this->traitUpdate();
    }
}
