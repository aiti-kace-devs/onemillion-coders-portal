<?php

namespace App\Http\Controllers\Traits;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
trait OverviewFieldTrait
{
    protected function addOverviewField()
    {
        CRUD::addField([
            'name' => 'overview',
            'label' => 'Course Overview',
            'type' => 'view',
            'view' => 'vendor.backpack.crud.fields.overview',
        ]);
    }
}