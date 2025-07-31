<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CentreRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\Branch;
use App\Models\Centre;
use App\Helpers\GeneralFieldsAndColumns;
use Illuminate\Http\Request;
use App\Helpers\FilterHelper;
use App\Helpers\WidgetHelper;
/**
 * Class CentreCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CentreCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Centre::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/centre');
        CRUD::setEntityNameStrings('centre', 'centres');

        $this->crud->operation('list', function () {
            WidgetHelper::centreStatisticsWidget();
        });
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('title')->type('textarea');
        CRUD::column('branch_id')->label('Branch')->linkTo('branch.show');
        FilterHelper::addBooleanColumn('status', 'status');
        CRUD::column('created_at');
        FilterHelper::addBooleanFilter('status');
        FilterHelper::addDateRangeFilter('created_at', 'Created At');
        CRUD::enableExportButtons();
    }


    protected function setupShowOperation()
    {
        $this->setupListOperation();
    }
    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(CentreRequest::class);
        CRUD::addField([
            'name' => 'title',
            'label' => 'Title',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

CRUD::addField([
    'name'        => 'branch_id',
    'label'       => 'Branch',
    'type'        => 'select',
    'entity'      => 'branch',
    'model'       => Branch::class,
    'attribute'   => 'title',
    'allows_null' => true,
    'default'     => null,
    'wrapper'     => ['class' => 'form-group col-6'],
]);


        $this->addIsActiveField([ true  => 'True', false => 'False'], 'Status', 'status');

    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }




    public function filterByBranch(Request $request)
    {
        $term = $request->input('term');
        $branchId = $request->input('branch_id');

        return Centre::where('branch_id', $branchId)
            ->when($term, fn($q) => $q->where('title', 'like', "%$term%"))
            ->get()
            ->map(fn($centre) => ['id' => $centre->id, 'text' => $centre->title]);
    }




}
