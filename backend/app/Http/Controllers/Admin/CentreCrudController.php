<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CentreRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\Branch;
use App\Models\Centre;
use App\Models\Constituency;
use App\Helpers\CourseFieldHelpers;
use App\Models\District;
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
    use CourseFieldHelpers;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }
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

        $this->crud->query->with('districts');

        CRUD::column('title')->type('textarea');
        CRUD::column('branch_id')->label('Region')->linkTo('branch.show');
        FilterHelper::addGenericRelationshipColumn('constituency', 'Constituency', 'constituency', 'title');
        CRUD::addColumn([
            'name' => 'districts',
            'label' => 'Districts',
            'type' => 'closure',
            'function' => function ($entry) {
                $districts = $entry->districts ?? collect();
                if ($districts->isEmpty()) {
                    return 'N/A';
                }

                return $districts
                    ->map(function ($district) {
                        $url = backpack_url('district/' . $district->id . '/show');
                        return '<a href="' . $url . '">' . e($district->title) . '</a>';
                    })
                    ->implode(', ');
            },
            'escaped' => false,
        ]);
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
        FilterHelper::addSelectFilter(
            'branch_id',
            'Region',
            Branch::query()->orderBy('title')->pluck('title', 'id')->toArray(),
            'select2'
        );
        FilterHelper::addSelectFilter(
            'constituency_id',
            'Constituency',
            Constituency::query()->orderBy('title')->pluck('title', 'id')->toArray(),
            'select2'
        );
        FilterHelper::addBooleanFilter('status');
        FilterHelper::addDateRangeFilter('created_at', 'Created At');
        CRUD::enableExportButtons();
    }


    protected function setupShowOperation()
    {
        CRUD::set('show.view', 'vendor.backpack.crud.centre_show');
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
            'label'       => 'Select Region',
            'type'        => 'select',
            'entity'      => 'branch',
            'model'       => Branch::class,
            'attribute'   => 'title',
            'allows_null' => true,
            'default'     => null,
            'wrapper'     => ['class' => 'form-group col-6'],
        ]);

        CRUD::addField([
            'name' => 'constituency_id',
            'label' => 'Select Constituency',
            'type' => 'select2_from_ajax',
            'entity' => 'constituency',
            'model' => Constituency::class,
            'attribute' => 'title',
            'data_source' => backpack_url('api/constituency-by-branch'),
            'dependencies' => ['branch_id'],
            'include_all_form_fields' => true,
            'minimum_input_length' => 0,
            'method' => 'GET',
            'wrapper' => ['class' => 'form-group col-6'],
            'attributes' => [
                'id' => 'constituency_id',
                'disabled' => 'disabled',
            ],
            'hint' => 'Select a region first to load constituencies.',
        ]);

        CRUD::addField([
            'name' => 'constituency_dependency_script',
            'type' => 'custom_html',
            'value' => view('admin.centre.fields.constituency_dependency_script'),
            'wrapper' => ['class' => 'd-none'],
        ]);

        $centre = $this->crud->getCurrentEntry();
        $selectedDistrictId = null;
        if ($centre instanceof Centre) {
            $selectedDistrictId = $centre->districts()->pluck('districts.id')->first();
        }

        CRUD::addField([
            'name' => 'district_id',
            'label' => 'Select District',
            'type' => 'select2_from_ajax',
            'entity' => false,
            'model' => District::class,
            'attribute' => 'title',
            'data_source' => backpack_url('api/district-by-branch'),
            'dependencies' => ['branch_id'],
            'include_all_form_fields' => true,
            'minimum_input_length' => 0,
            'method' => 'GET',
            'allows_null' => true,
            'wrapper' => ['class' => 'form-group col-6'],
            'attributes' => [
                'id' => 'district_id',
                'disabled' => 'disabled',
            ],
            'value' => $selectedDistrictId,
            'hint' => 'Select a region first to load districts.',
            'fake' => true,
        ]);

        CRUD::addField([
            'name' => 'district_dependency_script',
            'type' => 'custom_html',
            'value' => view('admin.centre.fields.district_dependency_script'),
            'wrapper' => ['class' => 'd-none'],
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

        $this->addFieldsToTab('General', true, ['title', 'branch_id', 'constituency_id', 'district_id', 'gps_address', 'pwd_notes']);
        $this->addFieldsToTab('PWD', true, ['is_pwd_friendly', 'wheelchair_accessible', 'has_access_ramp', 'has_accessible_toilet', 'has_elevator', 'supports_hearing_impaired', 'supports_visually_impaired', 'staff_trained_for_pwd', 'status']);

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

    public function store()
    {
        $response = $this->traitStore();
        $this->syncDistrictSelection();
        return $response;
    }

    public function update()
    {
        $response = $this->traitUpdate();
        $this->syncDistrictSelection();
        return $response;
    }

    protected function syncDistrictSelection(): void
    {
        $centre = $this->crud->getCurrentEntry();
        if (!$centre) {
            return;
        }

        $districtId = $this->crud->getRequest()->input('district_id');
        if ($districtId === null || $districtId === '') {
            $centre->districts()->sync([]);
            return;
        }

        $centre->districts()->sync([(int) $districtId]);
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
