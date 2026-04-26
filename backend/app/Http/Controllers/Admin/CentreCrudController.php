<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CentreSessionHelper;
use App\Helpers\CentreVisibilityHelper;
use App\Helpers\CourseFieldHelpers;
use App\Helpers\CrudListHelper;
use App\Helpers\FilterHelper;
use App\Helpers\GeneralFieldsAndColumns;
use App\Helpers\MediaHelper;
use App\Helpers\WidgetHelper;
use App\Http\Requests\CentreRequest;
use App\Models\Branch;
use App\Models\Centre;
use App\Models\Constituency;
use App\Models\District;
use App\Services\CentreDeletionService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Class CentreCrudController
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CentreCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }
    use CourseFieldHelpers;
    use GeneralFieldsAndColumns;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(Centre::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/centre');
        CRUD::setEntityNameStrings('centre', 'centres');

        $this->applyCurrentAdminCentreScope();

        if ($this->isCentreManager()) {
            CRUD::denyAccess(['create', 'update', 'delete']);
        }
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     *
     * @return void
     */
    protected function setupListOperation()
    {
        if ($this->isCentreManager()) {
            CRUD::denyAccess(['create', 'update', 'delete']);
        } else {
            WidgetHelper::centreStatisticsWidget();
        }
        CrudListHelper::editInDropdown(['crud::buttons.centre_add_session']);
        Widget::add([
            'name' => 'centre_sessions_modal',
            'type' => 'view',
            'view' => 'admin.centre.add_session_modal',
            'section' => 'after_content',
        ]);

        $this->crud->query->with('districts');

        if (! backpack_user()->can('centre.read.all') && ! backpack_user()->can('centre.read.self')) {
            abort(403, 'Unauthorized action.');
        }

        $centreAdmittedMetrics = DB::table('courses as c')
            ->join('admission_batches as ab', 'ab.id', '=', 'c.batch_id')
            ->leftJoin('user_admission as ua', function ($join) {
                $join->on('ua.course_id', '=', 'c.id')
                    ->whereNotNull('ua.confirmed');
            })
            ->leftJoin('users as u', 'u.userId', '=', 'ua.user_id')
            ->where('ab.completed', 0)
            ->where('ab.status', 1)
            ->selectRaw(
                '
                c.centre_id as centre_id,
                COUNT(DISTINCT ua.user_id) as total_admitted_users,
                COUNT(DISTINCT CASE WHEN u.support = 1 THEN ua.user_id END) as support_yes
            '
            )
            ->groupBy('c.centre_id')
            ->get()
            ->keyBy('centre_id');

        $supportMetricsQuery = DB::table('courses as c')
            ->join('admission_batches as ab', 'ab.id', '=', 'c.batch_id')
            ->leftJoin('user_admission as ua', function ($join) {
                $join->on('ua.course_id', '=', 'c.id')
                    ->whereNotNull('ua.confirmed');
            })
            ->leftJoin('users as u', 'u.userId', '=', 'ua.user_id')
            ->where('ab.completed', 0)
            ->where('ab.status', 1)
            ->selectRaw(
                '
                c.centre_id as centre_id,
                COUNT(DISTINCT CASE WHEN u.support = 1 THEN ua.user_id END) as support_yes
            '
            )
            ->groupBy('c.centre_id');

        $this->crud->query
            ->leftJoinSub($supportMetricsQuery, 'centre_support_metrics', function ($join) {
                $join->on('centres.id', '=', 'centre_support_metrics.centre_id');
            })
            ->select('centres.*')
            ->orderByRaw('COALESCE(centre_support_metrics.support_yes, 0) DESC')
            ->orderBy('centres.title');

        CRUD::column('title')->type('textarea')->label('Centre Name');

        if ($this->isCentreManager()) {
            CRUD::addColumn([
                'name' => 'branch',
                'label' => 'Region',
                'type' => 'closure',
                'function' => function ($entry) {
                    return $entry->branch?->title ?? '-';
                },
            ]);

            CRUD::addColumn([
                'name' => 'constituency',
                'label' => 'Constituency',
                'type' => 'closure',
                'function' => function ($entry) {
                    return $entry->constituency?->title ?? '-';
                },
            ]);

            CRUD::addColumn([
                'name' => 'districts',
                'label' => 'Districts',
                'type' => 'closure',
                'function' => function ($entry) {
                    $districts = $entry->districts ?? collect();
                    if ($districts->isEmpty()) {
                        return 'N/A';
                    }

                    return $districts->pluck('title')->implode(', ');
                },
            ]);

            CRUD::addColumn([
                'name' => 'total_admitted_users',
                'label' => 'Total Admitted Users',
                'type' => 'closure',
                'function' => function ($entry) use ($centreAdmittedMetrics) {
                    $metrics = $centreAdmittedMetrics->get((int) $entry->id);

                    return number_format((int) ($metrics->total_admitted_users ?? 0));
                },
            ]);

            CRUD::addColumn([
                'name' => 'support_yes',
                'label' => 'Users Who Needs Support',
                'type' => 'closure',
                'function' => function ($entry) use ($centreAdmittedMetrics) {
                    $metrics = $centreAdmittedMetrics->get((int) $entry->id);

                    return number_format((int) ($metrics->support_yes ?? 0));
                },
            ]);
        } else {
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
                            $url = backpack_url('district/'.$district->id.'/show');

                            return '<a href="'.$url.'">'.e($district->title).'</a>';
                        })
                        ->implode(', ');
                },
                'escaped' => false,
            ]);

            CRUD::addColumn([
                'name' => 'total_admitted_users',
                'label' => 'Total Admitted Users',
                'type' => 'closure',
                'function' => function ($entry) use ($centreAdmittedMetrics) {
                    $metrics = $centreAdmittedMetrics->get((int) $entry->id);

                    return number_format((int) ($metrics->total_admitted_users ?? 0));
                },
            ]);

            CRUD::addColumn([
                'name' => 'is_ready',
                'label' => 'Is Ready',
                'type' => 'view',
                'view' => 'admin.status_toggle.status_column',
                'toggleable' => true,
                'toggle_url' => 'centre/{id}/toggle-is-ready',
            ]);

            CRUD::addColumn([
                'name' => 'support_yes',
                'label' => 'Users Who Needs Support',
                'type' => 'closure',
                'function' => function ($entry) use ($centreAdmittedMetrics) {
                    $metrics = $centreAdmittedMetrics->get((int) $entry->id);

                    return number_format((int) ($metrics->support_yes ?? 0));
                },
            ]);

            CRUD::addColumn([
                'name' => 'status',
                'label' => 'Status',
                'type' => 'view',
                'view' => 'admin.status_toggle.status_column',
            ]);
        }
        // CRUD::column('created_at');
        if (! $this->isCentreManager()) {
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
        }
        FilterHelper::addBooleanFilter('is_ready');
        FilterHelper::addBooleanFilter('status');
        FilterHelper::addDateRangeFilter('created_at', 'Created At');
        CRUD::enableExportButtons();
    }

    protected function setupShowOperation()
    {
        if (! backpack_user()->can('centre.read.all') && ! backpack_user()->can('centre.read.self')) {
            abort(403, 'Unauthorized action.');
        }

        CRUD::set('show.view', 'vendor.backpack.crud.centre_show');
    }

    protected function isCentreManager(): bool
    {
        $admin = backpack_user();

        return $admin && method_exists($admin, 'hasRole') && $admin->hasRole('centre-manager');
    }

    protected function applyCurrentAdminCentreScope(): void
    {
        $visibleCentreIds = CentreVisibilityHelper::currentAdminVisibleCentreIds();

        if ($visibleCentreIds === null) {
            return;
        }

        if (empty($visibleCentreIds)) {
            $this->crud->addClause('whereRaw', '1 = 0');

            return;
        }

        $this->crud->addClause('whereIn', 'id', $visibleCentreIds);
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     *
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(CentreRequest::class);
        CRUD::addField([
            'name' => 'title',
            'label' => 'Title',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        CRUD::addField([
            'name' => 'branch_id',
            'label' => 'Select Region',
            'type' => 'select',
            'entity' => 'branch',
            'model' => Branch::class,
            'attribute' => 'title',
            'allows_null' => true,
            'default' => null,
            'wrapper' => ['class' => 'form-group col-6'],
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

        $centre = $this->resolveCurrentCentreEntry();
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

        $centreEntry = $this->resolveCurrentCentreEntry();
        $gpsAddressValue = $this->getExistingGpsAddress();
        $centreSessionsPayload = old('centre_sessions_payload', CentreSessionHelper::getFormPayload($centreEntry));

        CRUD::addField([
            'name' => 'gps_address',
            'label' => 'GPS Address',
            'type' => 'textarea',
            'value' => $gpsAddressValue,
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        CRUD::addField([
            'name' => 'gps_location',
            'label' => 'GPS Location',
            'type' => 'repeatable',
            'new_item_label' => 'Add location',
            'init_rows' => 1,
            'min_rows' => 0,
            'fields' => [
                [
                    'name' => 'Area',
                    'label' => 'Area',
                    'type' => 'text',
                    'wrapper' => ['class' => 'form-group col-6'],
                ],
                [
                    'name' => 'District',
                    'label' => 'District',
                    'type' => 'text',
                    'wrapper' => ['class' => 'form-group col-6'],
                ],
                [
                    'name' => 'AddressV1',
                    'label' => 'Address V1',
                    'type' => 'text',
                    'wrapper' => ['class' => 'form-group col-6'],
                ],
                [
                    'name' => 'GPSName',
                    'label' => 'GPS Name',
                    'type' => 'text',
                    'wrapper' => ['class' => 'form-group col-6'],
                ],
                [
                    'name' => 'PostCode',
                    'label' => 'Post Code',
                    'type' => 'text',
                    'wrapper' => ['class' => 'form-group col-6'],
                ],
                [
                    'name' => 'Street',
                    'label' => 'Street',
                    'type' => 'text',
                    'wrapper' => ['class' => 'form-group col-6'],
                ],
                [
                    'name' => 'Latitude',
                    'label' => 'Latitude',
                    'type' => 'number',
                    'attributes' => ['step' => 'any'],
                    'wrapper' => ['class' => 'form-group col-6'],
                ],
                [
                    'name' => 'Longitude',
                    'label' => 'Longitude',
                    'type' => 'number',
                    'attributes' => ['step' => 'any'],
                    'wrapper' => ['class' => 'form-group col-6'],
                ],
            ],
            'wrapper' => ['class' => 'form-group col-12'],
        ]);

        CRUD::addField([
            'name' => 'pwd_notes',
            'label' => 'PWD Notes',
            'type' => 'textarea',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        MediaHelper::getMediaSelector(
            name: 'images',
            multiple: true,
            label: 'Centre Images',
            disk_options: MediaHelper::getCentreImagesDiskOptions(),
            // wrapper_class: 'form-group col-12',
            value: $this->crud->getCurrentEntry() ? $this->crud->getCurrentEntry()->images ?? '' : '',
        );

        MediaHelper::getMediaSelector(
            name: 'video',
            multiple: false,
            label: 'Centre Video',
            disk_options: MediaHelper::getCentreVideoDiskOptions(),
            value: $this->crud->getCurrentEntry() ? $this->crud->getCurrentEntry()->video ?? '' : '',
        );

        // Capacity fields for programme batch quota management
        CRUD::addField([
            'name' => 'seat_count',
            'label' => 'Seat Count',
            'type' => 'number',
            'wrapper' => ['class' => 'form-group col-4'],
            'hint' => 'Maximum students per batch slot at this centre',
        ]);

        CRUD::addField([
            'name' => 'short_slots_per_day',
            'label' => 'Short Slots/Day',
            'type' => 'number',
            'wrapper' => ['class' => 'form-group col-4'],
            'hint' => 'Auto-derived from seat_count if not set (short courses: 2h/day)',
        ]);

        CRUD::addField([
            'name' => 'long_slots_per_day',
            'label' => 'Long Slots/Day',
            'type' => 'number',
            'wrapper' => ['class' => 'form-group col-4'],
            'hint' => 'Auto-derived from seat_count if not set (long courses: 4h/day)',
        ]);

        $this->addIsActiveField([true => 'True', false => 'False'], 'Is PWD Friendly', 'is_pwd_friendly');

        $this->addIsActiveField([true => 'True', false => 'False'], 'Wheelchair Accessible', 'wheelchair_accessible');

        $this->addIsActiveField([true => 'True', false => 'False'], 'Has Access Ramp', 'has_access_ramp');

        $this->addIsActiveField([true => 'True', false => 'False'], 'Has Accessible Toilet', 'has_accessible_toilet');

        $this->addIsActiveField([true => 'True', false => 'False'], 'Has Elevator', 'has_elevator');

        $this->addIsActiveField([true => 'True', false => 'False'], 'Supports Hearing Impaired', 'supports_hearing_impaired');

        $this->addIsActiveField([true => 'True', false => 'False'], 'Supports Visually Impaired', 'supports_visually_impaired');

        $this->addIsActiveField([true => 'True', false => 'False'], 'Staff Trained for PWDs', 'staff_trained_for_pwd');

        $this->addIsActiveField([true => 'True', false => 'False'], 'Is Ready', 'is_ready');

        $this->addIsActiveField([true => 'True', false => 'False'], 'Status', 'status');

        CRUD::addField([
            'name' => 'centre_sessions_manager',
            'type' => 'custom_html',
            'value' => view('admin.centre.fields.session_manager', [
                'centreEntry' => $centreEntry,
                'initialSessionsPayload' => $centreSessionsPayload,
            ]),
            'wrapper' => ['class' => 'form-group col-12'],
        ]);

        $this->addFieldsToTab('General', true, [
            'title', 'branch_id', 'constituency_id', 'constituency_dependency_script',
            'district_id', 'district_dependency_script', 'gps_address', 'pwd_notes', 'images', 'video',
            'seat_count', 'short_slots_per_day', 'long_slots_per_day',
        ]);
        $this->addFieldsToTab('PWD', true, ['is_pwd_friendly', 'wheelchair_accessible', 'has_access_ramp', 'has_accessible_toilet', 'has_elevator', 'supports_hearing_impaired', 'supports_visually_impaired', 'staff_trained_for_pwd', 'is_ready', 'status']);
        $this->addFieldsToTab('GPS Location', true, ['gps_location']);
        $this->addFieldsToTab('Sessions', true, ['centre_sessions_manager']);

    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     *
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function store()
    {
        $this->prepareGpsFields();
        $this->normalizeImagesPath();
        $this->normalizeVideoPath();
        $centreSessionRows = CentreSessionHelper::extractRowsFromPayload($this->crud->getRequest());
        $response = $this->traitStore();
        $this->syncDistrictSelection();
        CentreSessionHelper::syncAfterCrud($this->crud->getCurrentEntry(), $centreSessionRows);

        return $response;
    }

    public function update()
    {
        $this->prepareGpsFields();
        $this->normalizeImagesPath();
        $this->normalizeVideoPath();
        $centreSessionRows = CentreSessionHelper::extractRowsFromPayload($this->crud->getRequest());
        $response = $this->traitUpdate();
        $this->syncDistrictSelection();
        CentreSessionHelper::syncAfterCrud($this->crud->getCurrentEntry(), $centreSessionRows);

        return $response;
    }

    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');

        $id = $this->crud->getCurrentEntryId() ?: $id;
        $centre = Centre::findOrFail($id);

        app(CentreDeletionService::class)->delete($centre);

        return '1';
    }

    protected function prepareGpsFields(): void
    {
        $request = $this->crud->getRequest();
        $gpsAddressInput = trim((string) $request->input('gps_address'));
        $gpsLocationInput = $this->normalizeGpsLocationInput($request->input('gps_location'));
        $currentAddress = $this->getExistingGpsAddress();
        $normalizedNew = $this->normalizeGpsAddressForCompare($gpsAddressInput);
        $normalizedCurrent = $this->normalizeGpsAddressForCompare($currentAddress);
        $addressChanged = $normalizedNew !== '' && $normalizedNew !== $normalizedCurrent;

        $gpsLocation = $gpsLocationInput;
        if ($gpsAddressInput !== '' && $addressChanged) {
            $apiLocation = $this->fetchGpsLocationFromApi($gpsAddressInput);
            $gpsLocation = $apiLocation ? $this->buildGpsLocationPayload($apiLocation) : [];
        }

        if ($gpsAddressInput !== '') {
            $request->request->set('gps_address', $gpsAddressInput);
        }

        if (! empty($gpsLocation)) {
            $request->request->set('gps_location', $gpsLocation);
        }
    }

    protected function getExistingGpsAddress(): string
    {
        $centre = $this->resolveCurrentCentreEntry();
        if (! $centre) {
            return '';
        }

        $gpsAddress = $centre->gps_address;

        if (is_array($gpsAddress)) {
            return (string) ($gpsAddress['address'] ?? '');
        }

        if (is_string($gpsAddress)) {
            $decoded = json_decode($gpsAddress, true);
            if (is_array($decoded)) {
                return (string) ($decoded['address'] ?? '');
            }
            if (is_string($decoded)) {
                return $decoded;
            }
        }

        return (string) ($gpsAddress ?? '');
    }

    protected function resolveCurrentCentreEntry(): ?Centre
    {
        $entry = $this->crud->getCurrentEntry();
        if ($entry instanceof Centre) {
            return $entry;
        }

        $entryId = $this->crud->getCurrentEntryId()
            ?: $this->crud->getRequest()->route('id')
            ?: $this->crud->getRequest()->route('centreId')
            ?: $this->crud->getRequest()->route('centre');

        if (! $entryId) {
            return null;
        }

        try {
            $resolvedEntry = $this->crud->getEntry((int) $entryId);
            if ($resolvedEntry instanceof Centre) {
                return $resolvedEntry;
            }
        } catch (\Throwable $e) {
        }

        return Centre::find($entryId);
    }

    protected function normalizeGpsAddressForCompare(string $address): string
    {
        $normalized = strtoupper(trim($address));

        return $normalized;
    }

    protected function normalizeGpsLocationInput($input): array
    {
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            $input = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($input)) {
            return [];
        }

        $rows = array_values(array_filter($input, function ($row) {
            if (! is_array($row)) {
                return false;
            }

            foreach ($row as $value) {
                if ($value !== null && $value !== '') {
                    return true;
                }
            }

            return false;
        }));

        return $rows;
    }

    protected function fetchGpsLocationFromApi(string $gpsAddress): ?array
    {
        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post('https://ghanapostgps.sperixlabs.org/get-location', [
                    'address' => $gpsAddress,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $payload = $response->json();
            if (! ($payload['found'] ?? false)) {
                return null;
            }

            $table = $payload['data']['Table'] ?? [];
            if (! is_array($table) || empty($table)) {
                return null;
            }

            return is_array($table[0] ?? null) ? $table[0] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function buildGpsLocationPayload(array $location): array
    {
        $coordinates = $this->deriveCoordinatesFromLocation($location);

        return [[
            'Area' => $location['Area'] ?? null,
            'District' => $location['District'] ?? null,
            'AddressV1' => $location['AddressV1'] ?? null,
            'GPSName' => $location['GPSName'] ?? null,
            'PostCode' => $location['PostCode'] ?? null,
            'Street' => $location['Street'] ?? null,
            'Latitude' => $coordinates['latitude'],
            'Longitude' => $coordinates['longitude'],
        ]];
    }

    protected function deriveCoordinatesFromLocation(array $location): array
    {
        $latitude = $this->averageCoordinates(
            $this->toFloat($location['NorthLat'] ?? null),
            $this->toFloat($location['SouthLat'] ?? null)
        );

        $longitude = $this->averageCoordinates(
            $this->toFloat($location['EastLong'] ?? null),
            $this->toFloat($location['WestLong'] ?? null)
        );

        if ($latitude === null) {
            $latitude = $this->toFloat($location['CenterLatitude'] ?? null);
        }

        if ($longitude === null) {
            $longitude = $this->toFloat($location['CenterLongitude'] ?? null);
        }

        return ['latitude' => $latitude, 'longitude' => $longitude];
    }

    protected function averageCoordinates(?float $first, ?float $second): ?float
    {
        if ($first === null && $second === null) {
            return null;
        }

        if ($first === null) {
            return $second;
        }

        if ($second === null) {
            return $first;
        }

        return ($first + $second) / 2;
    }

    protected function toFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    protected function syncDistrictSelection(): void
    {
        $centre = $this->crud->getCurrentEntry();
        if (! $centre) {
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

    public function toggleIsReady(Request $request, $id)
    {
        $this->crud->hasAccessOrFail('update');

        $data = $request->validate([
            'value' => 'required|boolean',
        ]);

        $centre = Centre::findOrFail($id);
        $centre->is_ready = (bool) $data['value'];
        $centre->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Centre ready status updated successfully.',
            'value' => $centre->is_ready ? 1 : 0,
        ]);
    }

    /**
     * Return existing sessions for a centre in JSON.
     */
    public function getCentreSessions($centreId)
    {
        $centre = Centre::findOrFail($centreId);
        CentreSessionHelper::ensureAccess($centre);

        return response()->json([
            'centre_id' => $centre->id,
            'sessions' => CentreSessionHelper::getSessionsCollection($centre),
        ]);
    }

    /**
     * Create/update centre sessions from repeatable modal rows.
     */
    public function saveCentreSessions($centreId)
    {
        $this->crud->hasAccessOrFail('update');

        $centre = Centre::findOrFail($centreId);
        CentreSessionHelper::ensureAccess($centre);

        try {
            $rows = CentreSessionHelper::validateAndNormalizeRows(request()->input('sessions', []));
            CentreSessionHelper::persist($centre, $rows);
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', collect($e->errors())->flatten()->first() ?: 'Please review the submitted centre sessions.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->with('error', 'Unable to save centre sessions right now. Please try again.');
        }

        return redirect()
            ->back()
            ->with('success', 'Centre sessions saved successfully. New sessions were added to all centres, while existing session edits stayed on this centre.');
    }

    public function filterByBranch(Request $request)
    {
        $term = $request->input('term');
        $branchId = $request->input('branch_id');

        return Centre::where('branch_id', $branchId)
            ->when($term, fn ($q) => $q->where('title', 'like', "%$term%"))
            ->get()
            ->map(fn ($centre) => ['id' => $centre->id, 'text' => $centre->title]);
    }

    protected function normalizeImagesPath()
    {
        $request = $this->crud->getRequest();
        $imagePaths = $request->input('images');

        if (empty($imagePaths)) {
            return;
        }

        // Handle if input is a JSON string representing an array
        if (is_string($imagePaths)) {
            $decoded = json_decode($imagePaths, true);
            if (is_array($decoded)) {
                $imagePaths = $decoded;
            } else {
                $imagePaths = [$imagePaths];
            }
        }

        if (! is_array($imagePaths)) {
            return;
        }

        $normalizedImages = [];
        foreach ($imagePaths as $imagePath) {
            if (is_string($imagePath)) {
                // Check if it's a JSON string of paths
                $decoded = json_decode($imagePath, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $subPath) {
                        $normalized = $this->normalizeSingleImagePath($subPath);
                        if ($normalized) {
                            $normalizedImages[] = $normalized;
                        }
                    }
                } else {
                    $normalized = $this->normalizeSingleImagePath($imagePath);
                    if ($normalized) {
                        $normalizedImages[] = $normalized;
                    }
                }
            } elseif (is_array($imagePath)) {
                foreach ($imagePath as $subPath) {
                    $normalized = $this->normalizeSingleImagePath($subPath);
                    if ($normalized) {
                        $normalizedImages[] = $normalized;
                    }
                }
            }
        }

        $request->merge(['images' => $normalizedImages]);
    }

    protected function normalizeSingleImagePath($imagePath)
    {
        if (empty($imagePath)) {
            return '';
        }

        // If it already has https://, don't process further
        if (strpos($imagePath, 'https://') === 0 || strpos($imagePath, 'http://') === 0) {
            return $imagePath;
        }

        // Strip "Google Cloud Storage/" or similar disk aliases
        if (strpos($imagePath, CLOUD_STORAGE_ALIAS.'/') === 0) {
            $imagePath = substr($imagePath, strlen(CLOUD_STORAGE_ALIAS.'/'));
        }

        // Build the full CDN URL
        $cdnUrl = rtrim(config('filesystems.cdn_url'), '/');

        return $cdnUrl.'/'.ltrim($imagePath, '/');
    }

    protected function normalizeVideoPath()
    {
        $request = $this->crud->getRequest();
        $videoPaths = $request->input('video');

        if (empty($videoPaths)) {
            return;
        }

        // Handle if input is a JSON string representing an array
        if (is_string($videoPaths)) {
            $decoded = json_decode($videoPaths, true);
            if (is_array($decoded)) {
                $videoPaths = $decoded;
            } else {
                $videoPaths = [$videoPaths];
            }
        }

        if (! is_array($videoPaths)) {
            return;
        }

        $normalizedVideo = null;
        foreach ($videoPaths as $videoPath) {
            if (is_string($videoPath)) {
                // Check if it's a JSON string of paths
                $decoded = json_decode($videoPath, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $subPath) {
                        $normalized = $this->normalizeSingleVideoPath($subPath);
                        if ($normalized) {
                            $normalizedVideo = $normalized;
                            break 2;
                        }
                    }
                } else {
                    $normalized = $this->normalizeSingleVideoPath($videoPath);
                    if ($normalized) {
                        $normalizedVideo = $normalized;
                        break;
                    }
                }
            } elseif (is_array($videoPath)) {
                foreach ($videoPath as $subPath) {
                    $normalized = $this->normalizeSingleVideoPath($subPath);
                    if ($normalized) {
                        $normalizedVideo = $normalized;
                        break 2;
                    }
                }
            }
        }

        $request->merge(['video' => $normalizedVideo]);
    }

    protected function normalizeSingleVideoPath($videoPath)
    {
        if (empty($videoPath)) {
            return '';
        }

        // If it already has https://, don't process further
        if (strpos($videoPath, 'https://') === 0 || strpos($videoPath, 'http://') === 0) {
            return $videoPath;
        }

        // Strip "Google Cloud Storage/" or similar disk aliases
        if (strpos($videoPath, CLOUD_STORAGE_ALIAS.'/') === 0) {
            $videoPath = substr($videoPath, strlen(CLOUD_STORAGE_ALIAS.'/'));
        }

        // Build the full CDN URL
        $cdnUrl = rtrim(config('filesystems.cdn_url'), '/');

        return $cdnUrl.'/'.ltrim($videoPath, '/');
    }
}
