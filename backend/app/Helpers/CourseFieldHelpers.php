<?php

namespace App\Helpers;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\FilterHelper;
use App\Helpers\GeneralFieldsAndColumns;
use App\Models\Centre;
use App\Models\Programme;
use App\Models\Branch;
use App\Models\Course;
trait CourseFieldHelpers
{

    use FormHelper;
    use GeneralFieldsAndColumns;



    public static function addOngoingCoursesFilter(): void
{
    CRUD::filter('ongoing')
        ->type('simple')
        ->label('Ongoing Courses')
        ->whenActive(function () {
            CRUD::addClause('whereDate', 'start_date', '<=', now()->toDateString());
            CRUD::addClause('whereDate', 'end_date', '>=', now()->toDateString());
        });
}



public static function upcomingCourseSessionsFilter(): void
{
    CRUD::filter('upcoming_sessions')
        ->type('simple')
        ->label('Upcoming Sessions')
        ->whenActive(function () {
            CRUD::addClause('where', 'course_time', '>', now());
        });
}




    protected function setupCommonFields()
{

    $entry = $this->crud->getCurrentEntry();
    $derivedBranchId = $entry?->centre?->branch_id ?? null;

CRUD::addField([
    'name' => 'branch_id',
    'label' => 'Branch',
    'type' => 'select',
    'model' => Branch::class,
    'attribute' => 'title',
    'allows_null' => true,
    'wrapper' => ['class' => 'form-group col-6'],
    'value' => $derivedBranchId,
    'fake' => true,
]);




CRUD::addField([
    'name' => 'centre_id',
    'label' => 'Centre',
    'type' => 'select2_from_ajax',
    'attribute' => 'title',
    'data_source' => backpack_url('api/centre-by-branch'),
    'dependencies' => ['branch_id'],
    'method' => 'GET',
    'include_all_form_fields' => true,
    'placeholder' => 'Select a branch first',
    'minimum_input_length' => 0,
    'wrapper' => ['class' => 'form-group col-6'],
    'model' => Centre::class,
    'value' => $this->crud->getCurrentEntry()?->centre_id ?? null,
]);



CRUD::addField([
    'name' => 'programme_id',
    'label' => 'Programme',
    'type' => 'select2',
    'entity' => 'programme',
    'attribute' => 'title',
    'model' => Programme::class,
    'allows_null' => false,
    'wrapper' => ['class' => 'form-group col-6'],
]);

CRUD::addField([
    'name' => 'duration',
    'label' => 'Duration',
    'type' => 'select_from_array',
    'options' => [
        '1 Week' => '1 Week',
        '2 Week' => '2 Weeks',
        '3 Weeks' => '3 Weeks',
        '4 Weeks' => '4 Weeks',
        '1 Month' => '1 Month',
        '2 Months' => '2 Months',
        '3 Months' => '3 Months',
        '4 Months' => '4 Months',
    ],
    'wrapper' => ['class' => 'form-group col-6'],
]);

        CRUD::addField([
            'name' => 'start_date',
            'label' => 'Start Date',
            'type'      => 'date',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        CRUD::addField([
            'name' => 'end_date',
            'label' => 'End Date',
            'type'      => 'date',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        $this->addIsActiveField([ true  => 'True', false => 'False'], 'Status', 'status');

}






    protected function courseSessionFields()
{

    CRUD::addField([
    'name' => 'name',
    'type' => 'hidden',
]);


CRUD::addField([
    'name' => 'course_id',
    'label' => 'Course',
    'type' => 'select2',
    'entity' => 'course',
    'attribute' => 'course_name',
    'model' => Course::class,
    'allows_null' => false,
    'wrapper' => ['class' => 'form-group col-6'],
]);


        CRUD::addField([
            'name' => 'limit',
            'label' => 'Limit',
            'type'      => 'number',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);


CRUD::addField([
    'name' => 'session',
    'label' => 'Session',
    'type' => 'select_from_array',
    'options' => [
        '' => '',
        'Morning' => 'Morning',
        'Afternoon' => 'Afternoon',
        'Evening' => 'Evening',
    ],
    'wrapper' => ['class' => 'form-group col-6'],
]);

        CRUD::addField([
            'name' => 'course_time',
            'label' => 'Duration',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
            'hint' => 'eg. 8am - 1pm'
        ]);

        $this->addIsActiveField([ true  => 'True', false => 'False'], 'Status', 'status');


        CRUD::addField([
            'name' => 'link',
            'label' => 'Link',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
            'hint' => 'eg. https://chat.whatsapp.com/BekTu3PWEqc8UtydifN8Mt'
        ]);


}


    protected function courseColumn(string $pathName, string $columnName = null)
{
    CRUD::addColumn([
        'name' => 'course',
        'label' => 'Course',
        'type' => 'closure',
        'function' => function($entry) use ($pathName, $columnName) {
            if ($entry->course) {
                $url = backpack_url($pathName . '/' . $entry->course->id . '/show');
                return '<a href="' . $url . '">' . e($entry->course->$columnName) . '</a>';
            }
            return '-';
        },
        'escaped' => false,
    ]);
}



    public function addCourseField(): void
    {
        CRUD::addColumn([
            'name' => 'course',
            'label' => 'Course',
            'type' => 'closure',
            'function' => function($entry) {
                if ($entry->course) {
                    $url = backpack_url('course/' . $entry->course->id . '/show');
                    return '<a href="' . $url . '">' . e($entry->course->course_name) . '</a>';
                }
                return '-';
            },
            'escaped' => false,
        ]);
    } 


    public static function courseFilter(string $columnName): void
{
    $coursesArray = Course::orderBy('course_name')->pluck('course_name', 'id')->toArray();
    FilterHelper::addSelectFilter(
            columnName: $columnName,
            label: 'Course',
            options: $coursesArray,
            callback: function ($value) use ($columnName) {
                CRUD::addClause('where', $columnName, $value);
            }
        );
}





}
