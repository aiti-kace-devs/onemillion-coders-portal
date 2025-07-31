<?php

namespace App\Helpers;

trait FormHelper
{
    public function addFieldsToTab($tabName = 'Details', $field = true, $fieldNames =  [])
    {
        $fieldNames = count($fieldNames) == 0 ? collect($this->crud->getFields())->pluck('name')->all() : $fieldNames;
        foreach ($fieldNames as $fieldName) {
            $this->crud->{$field ? 'field' : 'column'}($fieldName)->tab($tabName);
        }
    }

    public function addWrapperClassToFields($classNames = 'form-group col-md-6 col-sm-12 bold-labels', $fieldNames =  [])
    {
        $fieldNames = count($fieldNames) == 0 ? collect($this->crud->getFields())->pluck('name')->all() : $fieldNames;
        foreach ($fieldNames as $fieldName) {
            $this->crud->field($fieldName)->wrapper(['class' => $classNames . ' mb-3']);
        }
    }

    public function twoColumnFields($fieldNames =  [])
    {
        $fieldNames = count($fieldNames) == 0 ? collect($this->crud->getFields())->pluck('name')->all() : $fieldNames;
        $this->addWrapperClassToFields('form-group col-md-6 col-sm-12 bold-labels', $fieldNames);
    }

    public function threeColumnFields($fieldNames =  [])
    {
        $fieldNames = count($fieldNames) == 0 ? collect($this->crud->getFields())->pluck('name')->all() : $fieldNames;
        $this->addWrapperClassToFields('form-group col-md-4 col-sm-12 bold-labels', $fieldNames);
    }
    public function oneColumnFields($fieldNames =  [])
    {
        $fieldNames = count($fieldNames) == 0 ? collect($this->crud->getFields())->pluck('name')->all() : $fieldNames;
        $this->addWrapperClassToFields('form-group col-sm-12 bold-labels', $fieldNames);
    }
}
