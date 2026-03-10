<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StudentVerificationRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\WidgetHelper;
use App\Helpers\CourseFieldHelpers;
use App\Helpers\FilterHelper;
use App\Helpers\CourseVisibilityHelper;
use App\Models\Centre;
use App\Models\Course;

/**
 * Class StudentVerificationCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class StudentVerificationCrudController extends CrudController
{
    use CourseFieldHelpers;
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
        CRUD::setModel(\App\Models\StudentVerification::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/student-verification');
        CRUD::setEntityNameStrings('student verification', 'student verifications');

        CRUD::denyAccess('create');
        CRUD::denyAccess('update');
        CRUD::denyAccess('delete');
        CRUD::denyAccess('show');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        WidgetHelper::verificationStatisticsWidget();
        $this->applyCurrentAdminVerificationCourseScope();

        CRUD::addColumn([
            'name' => 'name_previous_name',
            'label' => 'Fullname (Previous Name)',
            'type' => 'model_function',
            'function_name' => 'getNameWithPreviousName',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhere('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('previous_name', 'like', '%' . $searchTerm . '%');
            },
            'escaped' => false,
        ]);
        CRUD::column('email');
        CRUD::addColumn([
            'name' => 'card_type_upper_case',
            'label' => 'Card Type',
            'type' => 'model_function',
            'function_name' => 'getCardTypeUpperCase',
        ]);
        CRUD::column('ghcard')->lable('Card Number');
        CRUD::addColumn([
            'name' => 'verify_by_on',
            'label' => 'Verification BY (On)',
            'type' => 'model_function',
            'function_name' => 'getVerifiedByWithDate',
            'searchLogic' => false,
            'escaped' => false,
        ]);

        CRUD::addButton('line', 'verification_status', 'view', 'crud::buttons.verification_status');
        $currentAdmin = backpack_user();
        if ($currentAdmin instanceof Admin && $currentAdmin->isSuper()) {
            $this->addStudentBatchFilter('admission', 'Student Batch');
            $this->addCurrentAdminVerificationCourseFilter('registered_course');
            $admins = Admin::whereIn('id', function ($query) {
            $query->select('verified_by')
                ->from('users')
                ->whereNotNull('verified_by')
                ->groupBy('verified_by');
            })->pluck('name', 'id')->toArray();

            CRUD::filter('verified_by')
                ->type('select2')
                ->label('Verification BY')
                ->values($admins)
                ->whenActive(function ($value) {
                    CRUD::addClause('where', 'verified_by', $value);
                });
        }
        
        
        FilterHelper::addNullableColumnFilter('verification_status', 'verification_date', 'Verified');
        FilterHelper::addDateRangeFilter('verification_date', 'Verification Date');
        CRUD::enableExportButtons();
    }

    /**
     * Restrict student verification records by current admin's assigned courses.
     */
    protected function applyCurrentAdminVerificationCourseScope(): void
    {
        $visibleCourseIds = CourseVisibilityHelper::currentAdminVisibleCourseIds();

        if ($visibleCourseIds === null) {
            return;
        }

        if (empty($visibleCourseIds)) {
            CRUD::addClause('whereRaw', '1 = 0');
            return;
        }

        CRUD::addClause('whereIn', 'registered_course', $visibleCourseIds);
    }

    /**
     * Add course filter options limited to the current admin's visible courses.
     */
    protected function addCurrentAdminVerificationCourseFilter(string $columnName = 'registered_course', string $label = 'Course'): void
    {
        $coursesQuery = Course::query()->orderBy('course_name');
        $visibleCourseIds = CourseVisibilityHelper::currentAdminVisibleCourseIds();

        if (is_array($visibleCourseIds)) {
            if (empty($visibleCourseIds)) {
                $courseOptions = [];
            } else {
                $courseOptions = $coursesQuery
                    ->whereIn('id', $visibleCourseIds)
                    ->pluck('course_name', 'id')
                    ->toArray();
            }
        } else {
            $courseOptions = $coursesQuery->pluck('course_name', 'id')->toArray();
        }

        FilterHelper::addSelectFilter(
            columnName: $columnName,
            label: $label,
            options: $courseOptions,
            type: 'select2_multiple',
            callback: function ($value) use ($columnName) {
                $values = is_array($value) ? $value : explode(',', $value);
                CRUD::addClause('whereIn', $columnName, $values);
            },
        );
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(StudentVerificationRequest::class);
        CRUD::setFromDb(); // set fields from db columns.

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
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



    public function resetVerification($id)
    {
        $student = \App\Models\StudentVerification::findOrFail($id);
        $student->update([
            'card_type' => null,
            'ghcard' => null,
            'verification_date' => null,
            'verified_by' => null,
        ]);

        \Alert::success('Verification has been reset successfully')->flash();
        return back();
    }
}
