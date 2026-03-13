<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FilterHelper;
use App\Helpers\WidgetHelper;
use App\Http\Requests\DistrictRequest;
use App\Models\Branch;
use App\Models\Centre;
use App\Models\District;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class DistrictCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class DistrictCrudController extends CrudController
{
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
        CRUD::setModel(District::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/district');
        CRUD::setEntityNameStrings('district', 'districts');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        WidgetHelper::districtStatisticsWidget();

        $this->crud->query->withCount('centres');

        CRUD::column('title')->label('Title');
        CRUD::column('description')->label('Description');
        CRUD::column('branch_id')->label('Region')->linkTo('branch.show');

        CRUD::addColumn([
            'name' => 'centres_count',
            'label' => 'Centres',
            'type' => 'closure',
            'function' => function ($entry) {
                $count = (int) ($entry->centres_count ?? 0);
                $url = backpack_url('district/' . $entry->id . '/edit');

                return "<a href='{$url}'>{$count}</a>";
            },
            'escaped' => false,
        ]);

        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
            'toggle_url' => 'district/{id}/toggle',
            'toggle_success_message' => 'District status updated successfully.',
            'toggle_error_message' => 'Error updating district status.',
        ]);
        CRUD::column('created_at')->label('Created At');

        FilterHelper::addSelectFilter(
            'branch_id',
            'Region',
            Branch::query()->orderBy('title')->pluck('title', 'id')->toArray(),
            'select2'
        );

        FilterHelper::addBooleanFilter('status');
        FilterHelper::addDateRangeFilter('created_at', 'Created At');
        CRUD::enableExportButtons();
    }

    /**
     * Define what happens when the Show operation is loaded.
     *
     * @return void
     */
    protected function setupShowOperation()
    {
        CRUD::set('show.setFromDb', false);
        CRUD::set('show.view', 'vendor.backpack.crud.district_show');
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(DistrictRequest::class);
        $this->setupDistrictFields(true);
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        CRUD::setValidation(DistrictRequest::class);
        $this->addCentresManagementSection();
        $this->setupDistrictFields();
        
    }

    protected function setupDistrictFields(bool $includeCreateCentresField = false): void
    {
        CRUD::addField([
            'name' => 'title',
            'label' => 'Title',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
            'tab' => 'General Info',
        ]);

        CRUD::addField([
            'name' => 'description',
            'label' => 'Description',
            'type' => 'textarea',
            'attributes' => ['rows' => 2],
            'wrapper' => ['class' => 'form-group col-6'],
            'tab' => 'General Info',
        ]);

        CRUD::addField([
            'name' => 'branch_id',
            'label' => 'Select Region',
            'type' => 'select',
            'entity' => 'branch',
            'model' => Branch::class,
            'attribute' => 'title',
            'wrapper' => ['class' => 'form-group col-6'],
            'tab' => 'General Info',
        ]);

        
        CRUD::addField([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'switch',
            'wrapper' => ['class' => 'form-group col-6'],
            'tab' => 'General Info',
        ]);

        if (!$includeCreateCentresField) {
            return;
        }

        CRUD::addField([
            'name' => 'centres',
            'label' => 'Assign Centres',
            'type' => 'select2_multiple',
            'entity' => 'centres',
            'attribute' => 'title',
            'model' => Centre::class,
            'pivot' => true,
            'options' => function ($query) {
                return $query->orderBy('title', 'ASC')->get();
            },
            'hint' => 'Optional: assign one or more centres now. You can also manage them from the edit page.',
            'wrapper' => ['class' => 'form-group col-12'],
            'tab' => 'Assign Centres',
        ]);
    }

    protected function addCentresManagementSection(): void
    {
        $district = $this->crud->getCurrentEntry();
        if (!$district) {
            return;
        }

        $district->loadMissing(['centres.branch', 'branch']);
        $assignedCentreIds = $district->centres->pluck('id');

        $availableCentres = Centre::query()
            ->select(['id', 'title', 'branch_id'])
            ->with('branch:id,title')
            ->when($assignedCentreIds->isNotEmpty(), function ($query) use ($assignedCentreIds) {
                $query->whereNotIn('id', $assignedCentreIds->all());
            })
            ->orderBy('title')
            ->get();

        CRUD::addField([
            'name' => 'centres_management',
            'type' => 'custom_html',
            'value' => view('admin.district.centres_management', [
                'district' => $district,
                'assignedCentres' => $district->centres->sortBy('title')->values(),
                'availableCentres' => $availableCentres,
            ]),
            'tab' => 'Assign Centres',
        ]);
    }

    public function addCentres($districtId)
    {
        $district = District::findOrFail($districtId);

        $data = request()->validate([
            'centre_ids' => 'required|array|min:1',
            'centre_ids.*' => 'required|integer|exists:centres,id',
        ]);

        $centreIds = collect($data['centre_ids'])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $validCentreIds = Centre::query()
            ->whereIn('id', $centreIds->all())
            ->pluck('id')
            ->all();

        if (empty($validCentreIds)) {
            return redirect()
                ->back()
                ->with('error', 'No valid centres were selected for this district.');
        }

        $syncResult = $district->centres()->syncWithoutDetaching($validCentreIds);
        $attachedCount = count($syncResult['attached'] ?? []);

        if ($attachedCount > 0) {
            return redirect()
                ->back()
                ->with('success', "{$attachedCount} centre(s) assigned successfully.");
        }

        return redirect()
            ->back()
            ->with('info', 'No new centres were assigned. Selected centres are already linked.');
    }

    public function removeCentre($districtId, $centreId)
    {
        $district = District::findOrFail($districtId);

        $isAssigned = $district->centres()
            ->where('centres.id', $centreId)
            ->exists();

        if (!$isAssigned) {
            return redirect()
                ->back()
                ->with('info', 'This centre is not assigned to the selected district.');
        }

        $district->centres()->detach($centreId);

        return redirect()
            ->back()
            ->with('success', 'Centre removed from district successfully.');
    }

    public function toggleStatus($id)
    {
        $district = District::findOrFail($id);

        $rawValue = request()->input('value');
        if ($rawValue === null) {
            return response()->json([
                'message' => 'Missing status value.',
            ], 422);
        }

        $parsedBoolean = filter_var($rawValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($parsedBoolean === null && !in_array($rawValue, [0, 1, '0', '1'], true)) {
            return response()->json([
                'message' => 'Invalid status value.',
            ], 422);
        }

        $newValue = $parsedBoolean ?? ((int) $rawValue === 1);

        $district->status = $newValue;
        $district->save();

        return response()->json([
            'message' => 'District status updated successfully.',
            'value' => $district->status ? 1 : 0,
        ]);
    }


    
}
