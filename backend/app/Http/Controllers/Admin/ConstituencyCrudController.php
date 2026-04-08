<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FilterHelper;
use App\Models\Branch;
use App\Helpers\WidgetHelper;
use App\Http\Requests\ConstituencyRequest;
use App\Models\Constituency;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\DB;
use App\Helpers\CrudListHelper;

/**
 * Class ConstituencyCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ConstituencyCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Constituency::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/constituency');
        CRUD::setEntityNameStrings('constituency', 'constituencies');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {

        CRUD::column('title')->label('Title');
        CRUD::column('description')->label('Description');
        CRUD::column('branch_id')->label('Region')->linkTo('branch.show');

        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
            'toggle_url' => 'constituency/{id}/toggle',
            'toggle_success_message' => 'Constituency status updated successfully.',
            'toggle_error_message' => 'Error updating constituency status.',
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
        CrudListHelper::editInDropdown();
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(ConstituencyRequest::class);
        $this->setupConstituencyFields();
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        CRUD::setValidation(ConstituencyRequest::class);
        $this->setupConstituencyFields();
    }

    /**
     * Define what happens when the Show operation is loaded.
     *
     * @return void
     */
    protected function setupShowOperation()
    {
        CRUD::set('show.setFromDb', false);
        CRUD::set('show.view', 'vendor.backpack.crud.constituency_show');
    }



    protected function setupConstituencyFields(bool $includeCreateCentresField = false): void
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



    }





    public function toggleStatus($id)
    {
        $constituency = Constituency::findOrFail($id);

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

        $constituency->status = $newValue;
        $constituency->save();

        return response()->json([
            'message' => 'Constituency status updated successfully.',
            'value' => $constituency->status ? 1 : 0,
        ]);
    }

    public function metrics(int $constituencyId)
    {
        $constituency = Constituency::findOrFail($constituencyId);

        $centreIds = DB::table('centres')
            ->where('constituency_id', $constituencyId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $totalCentres = count($centreIds);
        $courseIds = [];
        $totalCourses = 0;
        $totalRegisteredUsers = 0;
        $totalShortlistedUsers = 0;
        $admissionsTotal = 0;
        $admissionsConfirmed = 0;
        $admissionsPending = 0;
        $totalAdmittedUsers = 0;
        $coursesWithoutRegistrations = 0;

        $genderLabels = collect(['Male', 'Female']);
        $genderValues = collect([0, 0]);
        $genderCounts = collect();
        $ageLabels = collect();
        $ageValues = collect();

        if (!empty($centreIds)) {
            $courseIds = DB::table('courses')
                ->whereIn('centre_id', $centreIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $totalCourses = count($courseIds);

            if (!empty($courseIds)) {
                $totalRegisteredUsers = (int) DB::table('users')
                    ->whereIn('registered_course', $courseIds)
                    ->count('id');

                $totalShortlistedUsers = (int) DB::table('users')
                    ->whereIn('registered_course', $courseIds)
                    ->where(function ($query) {
                        $query->where('shortlist', 1)->orWhere('shortlist', true);
                    })
                    ->count('id');

                $admissionsAgg = DB::table('user_admission')
                    ->whereIn('course_id', $courseIds)
                    ->selectRaw('
                        COUNT(*) as total_count,
                        SUM(CASE WHEN confirmed IS NOT NULL THEN 1 ELSE 0 END) as confirmed_count,
                        SUM(CASE WHEN confirmed IS NULL THEN 1 ELSE 0 END) as pending_count,
                        COUNT(DISTINCT CASE WHEN confirmed IS NOT NULL THEN user_id END) as admitted_students_count
                    ')
                    ->first();

                $admissionsTotal = (int) ($admissionsAgg->total_count ?? 0);
                $admissionsConfirmed = (int) ($admissionsAgg->confirmed_count ?? 0);
                $admissionsPending = (int) ($admissionsAgg->pending_count ?? 0);
                $totalAdmittedUsers = (int) ($admissionsAgg->admitted_students_count ?? 0);

                $genderCounts = DB::table('users as u')
                    ->whereIn('u.registered_course', $courseIds)
                    ->selectRaw("
                        CASE
                            WHEN LOWER(TRIM(COALESCE(u.gender, ''))) IN ('male', 'm') THEN 'Male'
                            WHEN LOWER(TRIM(COALESCE(u.gender, ''))) IN ('female', 'f') THEN 'Female'
                            WHEN TRIM(COALESCE(u.gender, '')) = '' THEN 'Unspecified'
                            ELSE 'Other'
                        END as gender_label,
                        COUNT(*) as total
                    ")
                    ->groupBy('gender_label')
                    ->pluck('total', 'gender_label');

                $genderValues = $genderLabels
                    ->map(fn ($label) => (int) ($genderCounts[$label] ?? 0))
                    ->values();

                $ageCounts = DB::table('users as u')
                    ->whereIn('u.registered_course', $courseIds)
                    ->selectRaw("
                        CASE
                            WHEN u.age IS NULL OR u.age = '' THEN 'Unknown'
                            WHEN u.age LIKE '%-%' OR u.age LIKE '%–%' OR u.age LIKE '%—%' THEN u.age
                            WHEN u.age LIKE '%+%' THEN u.age
                            WHEN u.age REGEXP '^[0-9]+$' THEN
                                CONCAT(
                                    FLOOR(CAST(u.age AS UNSIGNED) / 10) * 10,
                                    '-',
                                    FLOOR(CAST(u.age AS UNSIGNED) / 10) * 10 + 9
                                )
                            ELSE 'Unknown'
                        END AS age_range,
                        COUNT(*) AS total,
                        CASE
                            WHEN u.age IS NULL OR u.age = '' THEN 9999
                            WHEN u.age LIKE '%-%' OR u.age LIKE '%–%' OR u.age LIKE '%—%' THEN
                                CAST(SUBSTRING_INDEX(u.age, '-', 1) AS UNSIGNED)
                            WHEN u.age LIKE '%+%' THEN
                                CAST(SUBSTRING_INDEX(u.age, '+', 1) AS UNSIGNED)
                            WHEN u.age REGEXP '^[0-9]+$' THEN
                                FLOOR(CAST(u.age AS UNSIGNED) / 10)
                            ELSE 9999
                        END AS bucket_order
                    ")
                    ->groupBy('age_range', 'bucket_order')
                    ->orderBy('bucket_order')
                    ->get();

                $ageLabels = $ageCounts->pluck('age_range')->values();
                $ageValues = $ageCounts->pluck('total')->map(fn ($v) => (int) $v)->values();

                $registeredByCourse = DB::table('users')
                    ->whereIn('registered_course', $courseIds)
                    ->selectRaw('registered_course as course_id, COUNT(id) as total')
                    ->groupBy('registered_course')
                    ->pluck('total', 'course_id');

                $coursesWithRegistrations = (int) $registeredByCourse->count();
                $coursesWithoutRegistrations = max($totalCourses - $coursesWithRegistrations, 0);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'constituency_id' => $constituency->getKey(),
                'total_registered_users' => $totalRegisteredUsers,
                'total_shortlisted_users' => $totalShortlistedUsers,
                'total_admitted_users' => $totalAdmittedUsers,
                'total_courses' => $totalCourses,
                'total_centres' => $totalCentres,
                'courses_without_registrations' => $coursesWithoutRegistrations,
                'pending_admissions' => $admissionsPending,
                'gender_distribution' => [
                    'labels' => $genderLabels->values(),
                    'values' => $genderValues->values(),
                    'raw' => $genderCounts,
                ],
                'age_group_distribution' => [
                    'labels' => $ageLabels->values(),
                    'values' => $ageValues->values(),
                ],
                'admissions_distribution' => [
                    'total' => $admissionsTotal,
                    'confirmed' => $admissionsConfirmed,
                    'pending' => $admissionsPending,
                    'admitted_users' => $totalAdmittedUsers,
                ],
            ],
        ]);
    }



}
