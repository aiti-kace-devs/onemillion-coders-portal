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
trait StudentFormFieldHelpers
{

    use FormHelper;
    use GeneralFieldsAndColumns;

    protected array $options = [ true  => 'True', false => 'False'];
    protected string $defaultBooleanLabelTrue = 'Active';
    protected string $defaultBooleanLabelFalse = 'Inactive';


    protected function setupCreateRegistrationFormFields()
{

        CRUD::addField([
            'name' => 'title',
            'label' => 'Title',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        CRUD::addField([
            'name' => 'code',
            'label' => 'Unique Code',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        CRUD::addField([
            'name' => 'message_after_registration',
            'label' => 'Message After Registration',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        CRUD::addField([
            'name' => 'message_when_inactive',
            'label' => 'Message When Inactive',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        CRUD::addField([
            'name' => 'description',
            'label' => 'Description',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        MediaHelper::getMediaSelector(
            name: 'image',
            disk_options: MediaHelper::getArticleImagesDiskOptions(),
            label: 'Cover Image',
            value: $entry->coverImage->file ?? '',
        );


        $this->addIsActiveField([ true  => 'True', false => 'False'], 'Active (Accept Responses)', 'active');


    CRUD::addField([
        'name' => 'schema',
        'label' => 'REGISTRATION FORM',
        'type' => 'repeatable',
        'fields' => [
            [
                'name' => 'title',
                'type' => 'text',
                'label' => 'Title',
                'wrapper' => ['class' => 'form-group col-md-6'],
                'hint' => 'eg. Full Name'
            ],
            [
                'name' => 'type',
                'label' => 'Field Type',
                'type' => 'select_from_array',
                'options' => [
                    'text' => 'Text',
                    'email' => 'Email',
                    'phonenumber' => 'Phonenumber',
                    'password' => 'Password',
                    'textarea' => 'Textarea',
                    'select' => 'Select',
                    'checkbox' => 'Checkbox',
                    'radio' => 'Radio',
                    'number' => 'Number',
                    'file' => 'File',
                    'select_course' => 'Course Selection',
                ],
                'wrapper' => ['class' => 'form-group col-md-6'],
            ],
            [
                'name' => 'description',
                'type' => 'textarea',
                'label' => 'Description',
                // 'wrapper' => ['class' => 'form-group col-md-6'],
                'hint' => 'eg. Your full name as appears on official documents'
            ],

            [
                'name' => 'rules',
                'type' => 'text',
                'label' => 'Validation Rules',
                'wrapper' => ['class' => 'form-group col-md-6'],
                'hint' => 'eg. regex:/^[\pL\s\-\']+$/u|min:5|max:255'
            ],
            [
                'name' => 'options',
                'type' => 'text',
                'label' => 'Options (Comma Separated)',
                'hint' => 'Only used for select, checkbox, radio fields. Eg Feild Type (Select) Options(Male,Female)',
                'wrapper' => ['class' => 'form-group col-md-6'],
            ],
            [
                'name' => 'validators.required',
                'type' => 'switch',
                'label' => 'Required',
                'options' => $options ?? [
                    0 => $this->defaultBooleanLabelFalse,
                    1 => $this->defaultBooleanLabelTrue,
                ],
                'wrapper' => ['class' => 'form-group col-6'],
            ],
            [
                'name' => 'validators.unique',
                'type' => 'switch',
                'label' => 'Unique',
                'options' => $options ?? [
                    0 => $this->defaultBooleanLabelFalse,
                    1 => $this->defaultBooleanLabelTrue,
                ],
                'wrapper' => ['class' => 'form-group col-6'],
            ],


        ],
        'new_item_label' => 'Add Question',
        'init_rows' => 0,
    ]);


        $this->addFieldsToTab('Info', true, ['title', 'code', 'message_after_registration', 'message_when_inactive', 'description', 'image', 'active']);
        $this->addFieldsToTab('Form', true, ['schema']);
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
