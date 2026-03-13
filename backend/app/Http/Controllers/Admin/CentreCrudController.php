<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CentreRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\Branch;
use App\Models\Centre;
use App\Helpers\GeneralFieldsAndColumns;
use Illuminate\Http\Request;
use App\Helpers\CrudListHelper;
use App\Helpers\FilterHelper;
use App\Helpers\MediaHelper;
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
        WidgetHelper::centreStatisticsWidget();

        CRUD::column('title')->type('textarea');
        CRUD::column('branch_id')->label('Branch')->linkTo('branch.show');
        CRUD::addColumn([
            'name' => 'is_pwd_friendly',
            'label' => 'Is PWD Friendly',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
            'toggle_url' => 'centre/{id}/toggle-is-pwd-friendly',
        ]);
        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
        ]);
        CRUD::column('created_at');
        FilterHelper::addBooleanFilter('status');
        FilterHelper::addDateRangeFilter('created_at', 'Created At');
        CRUD::enableExportButtons();
    }


    protected function setupShowOperation()
    {
        CRUD::column('title')->type('textarea');
        CRUD::column('branch_id')->label('Branch')->linkTo('branch.show');
        CRUD::column('gps_address');
        CRUD::column('pwd_notes');
        CRUD::addColumn([
            'name' => 'is_pwd_friendly',
            'label' => 'Is PWD Friendly',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
        ]);
        CRUD::addColumn([
            'name' => 'wheelchair_accessible',
            'label' => 'Wheelchair Accessible',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
        ]);
        CRUD::addColumn([
            'name' => 'has_access_ramp',
            'label' => 'Has Access Ramp',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
        ]);
        CRUD::addColumn([
            'name' => 'has_accessible_toilet',
            'label' => 'Has Accessible Toilet',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
        ]);
        CRUD::addColumn([
            'name' => 'has_elevator',
            'label' => 'Has Elevator',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
        ]);
        CRUD::addColumn([
            'name' => 'supports_hearing_impaired',
            'label' => 'Supports Hearing Impaired',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
        ]);
        CRUD::addColumn([
            'name' => 'supports_visually_impaired',
            'label' => 'Supports Visually Impaired',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
        ]);
        CRUD::addColumn([
            'name' => 'staff_trained_for_pwd',
            'label' => 'Staff Trained for PWDs',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
        ]);
        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
        ]);
        CRUD::column('created_at');
        CRUD::column('updated_at');
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
            'type'        => 'select2',
            'entity'      => 'branch',
            'model'       => Branch::class,
            'attribute'   => 'title',
            'allows_null' => true,
            'default'     => null,
            'wrapper'     => ['class' => 'form-group col-6'],
        ]);


        CRUD::addField([
            'name' => 'gps_address',
            'label' => 'GPS Address',
            'type'      => 'textarea',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);


        CRUD::addField([
            'name' => 'pwd_notes',
            'label' => 'PWD Notes',
            'type'      => 'textarea',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        MediaHelper::getMediaSelector(
            name: 'images',
            multiple: true,
            label: 'Centre Images',
            disk_options: MediaHelper::getArticleImagesDiskOptions(),
            // wrapper_class: 'form-group col-12',
            value: $this->crud->getCurrentEntry() ? $this->crud->getCurrentEntry()->coverImage->file ?? '' : '',
        );

        $this->addIsActiveField([ true  => 'True', false => 'False'], 'Is PWD Friendly', 'is_pwd_friendly');

        $this->addIsActiveField([ true  => 'True', false => 'False'], 'Wheelchair Accessible', 'wheelchair_accessible');

        $this->addIsActiveField([ true  => 'True', false => 'False'], 'Has Access Ramp', 'has_access_ramp');

        $this->addIsActiveField([ true  => 'True', false => 'False'], 'Has Accessible Toilet', 'has_accessible_toilet');

        $this->addIsActiveField([ true  => 'True', false => 'False'], 'Has Elevator', 'has_elevator');

        $this->addIsActiveField([ true  => 'True', false => 'False'], 'Supports Hearing Impaired', 'supports_hearing_impaired');

        $this->addIsActiveField([ true  => 'True', false => 'False'], 'Supports Visually Impaired', 'supports_visually_impaired');

        $this->addIsActiveField([ true  => 'True', false => 'False'], 'Staff Trained for PWDs', 'staff_trained_for_pwd');

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

    public function toggleStatus(Request $request, $id)
    {
        $this->crud->hasAccessOrFail('update');

        $data = $request->validate([
            'value' => 'required|boolean',
        ]);

        $centre = Centre::findOrFail($id);
        $centre->status = (bool) $data['value'];
        $centre->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Centre status updated successfully.',
            'value' => $centre->status ? 1 : 0,
        ]);
    }

    public function toggleIsPwdFriendly(Request $request, $id)
    {
        $this->crud->hasAccessOrFail('update');

        $data = $request->validate([
            'value' => 'required|boolean',
        ]);

        $centre = Centre::findOrFail($id);
        $centre->is_pwd_friendly = (bool) $data['value'];
        $centre->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Centre PWD accessibility updated successfully.',
            'value' => $centre->is_pwd_friendly ? 1 : 0,
        ]);
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
