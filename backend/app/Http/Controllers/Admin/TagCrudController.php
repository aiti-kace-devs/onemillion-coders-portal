<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\TagRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\CrudListHelper;

class TagCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Tag::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/tag');
        CRUD::setEntityNameStrings('tag', 'tags');
    }

    protected function setupListOperation()
    {
        CrudListHelper::editInDropdown();

        CRUD::column('name');
        CRUD::column('tag_type_id')->type('select')->entity('tagType')->attribute('name')->model('App\Models\TagType')->label('Tag Type');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'name' => 'required|min:2|max:255|unique:tags,name',
            'tag_type_id' => 'required|exists:tag_types,id',
        ]);

        CRUD::field('name');
        CRUD::addField([
            'name' => 'tag_type_id',
            'label' => 'Tag Type',
            'type' => 'relationship',
            'entity' => 'tagType',
            'attribute' => 'name',
            'model' => 'App\Models\TagType'
        ]);
    }

    protected function setupUpdateOperation()
    {
        CRUD::setValidation([
            'name' => 'required|min:2|max:255|unique:tags,name,' . $this->crud->getCurrentEntryId(),
            'tag_type_id' => 'required|exists:tag_types,id',
        ]);

        $this->setupCreateOperation();
    }
}
