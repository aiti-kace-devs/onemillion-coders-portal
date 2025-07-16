<?php

namespace App\Helpers;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\FilterHelper;
use App\Helpers\GeneralFieldsAndColumns;
use App\Models\Centre;
use App\Models\Programme;
use App\Models\Branch;
use App\Models\CourseCategory;
use App\Models\Course;
use App\Models\UserAdmission;
use App\Helpers\MediaHelper;
trait ProgrammeFieldHelpers
{

    use FormHelper;
    use GeneralFieldsAndColumns;


    public static function addOngoingCoursesFilter(string $label): void
{
    CRUD::filter('ongoing')
        ->type('simple')
        ->label($label)
        ->whenActive(function () {
            CRUD::addClause('whereDate', 'start_date', '<=', now()->toDateString());
            CRUD::addClause('whereDate', 'end_date', '>=', now()->toDateString());
        });
}




    protected function setupCommonFields()
{

        CRUD::addField([
            'name' => 'title',
            'label' => 'Title',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);


        CRUD::addField([
            'name' => 'sub_title',
            'label' => 'Sub Title',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        CRUD::addField([
            'name' => 'course_category_id',
            'label' => 'Course Category',
            'type' => 'select2',
            'entity' => 'category',
            'attribute' => 'title',
            'model' => CourseCategory::class,
            'allows_null' => false,
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        MediaHelper::getMediaSelector(
            name: 'coverImage',
            disk_options: MediaHelper::getArticleImagesDiskOptions(),
            label: 'Cover Image',
            value: $entry->coverImage->file ?? '',
        );


        CRUD::addField([
            'name' => 'description',
            'label' => 'Description',
            'type'      => 'textarea',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);


        CRUD::addField([
            'name' => 'duration',
            'label' => 'Duration',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
            'hint' => 'eg 3  Week or 120 hrs'
        ]);

        // CRUD::addField([
        //     'name' => 'duration',
        //     'label' => 'Duration',
        //     'type' => 'select_from_array',
        //     'options' => [
        //         '1 Week' => '1 Week',
        //         '2 Week' => '2 Weeks',
        //         '3 Weeks' => '3 Weeks',
        //         '4 Weeks' => '4 Weeks',
        //         '1 Month' => '1 Month',
        //         '2 Months' => '2 Months',
        //         '3 Months' => '3 Months',
        //         '4 Months' => '4 Months',
        //     ],
            // 'wrapper' => ['class' => 'form-group col-6'],
        // ]);

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


        CRUD::addField([
            'name' => 'overview',
            'label' => 'Overview',
            'type'      => 'tinymce',
            // 'wrapper' => ['class' => 'form-group col-6'],
        ]);

        $this->addIsActiveField([ true  => 'True', false => 'False'], 'Status', 'status');

        $this->addFieldsToTab('General Info', true, ['title', 'sub_title', 'coverImage', 'course_category_id', 'status', 'description']);
        $this->addFieldsToTab('Course Duration', true, ['duration', 'start_date', 'end_date']);
        $this->addFieldsToTab('Course Overview', true, ['overview']);
}





    protected function setupShowCommonFields()
{
        CRUD::addColumn([
            'name' => 'title',
            'type' => 'textarea',
            'escaped' => false,
        ]);
        CRUD::addColumn([
            'name' => 'sub_title',
            'type' => 'textarea',
            'escaped' => false,
        ]);
        CRUD::addColumn([
            'name' => 'description',
            'type' => 'textarea',
            'escaped' => false,
        ]);
        // FilterHelper::addBooleanFilter('status', 'Status');
        // FilterHelper::addGenericRelationshipColumn('category', 'Course Category', 'course-category', 'title');
        // CRUD::addColumn('created_on');
        // CRUD::addColumn('updated_on');
        // CRUD::addColumn('duration');
        // CRUD::addColumn('start_date');
        // CRUD::addColumn('end_date');
        // CRUD::addColumn([
        //     'name' => 'overview',
        //     'type' => 'tinymce',
        //     'escaped' => false,
        // ]);
        

        $this->addFieldsToTab('General', false, [
            'title',
            'sub_title',
            'description',
            // 'status',
            // 'course_category_id',
            // 'created_on',
            // 'updated_on',
        ]);

        // $this->addFieldsToTab('Duration', false, [
        //     'duration',
        //     'start_date',
        //     'end_date'
        // ]);

        // $this->addFieldsToTab('Overview', false, ['overview']);


}



}
