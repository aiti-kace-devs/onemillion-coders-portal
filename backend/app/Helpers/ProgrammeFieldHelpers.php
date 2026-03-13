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
use App\Models\CourseMatch;

use App\Models\UserAdmission;
use App\Helpers\MediaHelper;
use App\Http\Controllers\Traits\OverviewFieldTrait;
use App\Models\CourseMatchOption;

trait ProgrammeFieldHelpers
{

    use FormHelper;
    use GeneralFieldsAndColumns;
    use OverviewFieldTrait;


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




    protected function setupCreateFields()
    {

        $programme = $this->crud->getCurrentEntry() ?? null;
        $courseModules = [];
        $courseCertification = [];
        $overview = $programme?->overview ?? [];

        if ($programme) {
            $courseModules = $programme->courseModules()
                ->get(['title', 'description', 'status'])
                ->map(function ($module) {
                    return [
                        'title' => $module->title,
                        'description' => $module->description,
                        'status' => $module->status,
                    ];
                })->toArray();
        }

        if ($programme) {
            $courseCertification = $programme->courseCertification()
                ->get(['title', 'description', 'description', 'type', 'status'])
                ->map(function ($certificate) {
                    return [
                        'title' => $certificate->title,
                        'description' => $certificate->description,
                        'type' => $certificate->type,
                        'status' => $certificate->status,
                    ];
                })->toArray();
        }

        CRUD::addField([
            'name' => 'title',
            'label' => 'Title',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
            'hint' => 'eg. Data Analyst (Microsoft Option)'
        ]);

        CRUD::addField([
            'name' => 'sub_title',
            'label' => 'Sub Title',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
            'hint' => 'eg. Data Analyst Associate'
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
            name: 'image',
            disk_options: MediaHelper::getArticleImagesDiskOptions(),
            label: 'Cover Image',
            value: $entry->coverImage->file ?? '',
        );


        // CRUD::addField([
        //     'name' => 'image',
        //     'label' => 'Cover Image URL',
        //     'type' => 'text',
        //     'wrapper' => ['class' => 'form-group col-6'],
        //     'hint' => 'Copy and paste image URL eg. https://cdn.msme.gikace.org/media/image/partners/undp-logo.png'
        // ]);

        CRUD::addField([
            'name' => 'start_date',
            'label' => 'Start Date',
            'type' => 'date',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        CRUD::addField([
            'name' => 'end_date',
            'label' => 'End Date',
            'type' => 'date',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);



        CRUD::addField([
            'name' => 'duration',
            'label' => 'Duration',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
            'hint' => 'eg 3  Week or 120 hrs'
        ]);


        CRUD::addField([
            'name' => 'level',
            'label' => 'Course Level',
            'type' => 'select_from_array',
            'options' => [
                'Beginner' => 'Beginner',
                'Intermediate' => 'Intermediate',
                'Advanced' => 'Advanced',
            ],
            'allows_null' => false,
            'wrapper' => ['class' => 'form-group col-6'],
            'hint' => 'Select the course difficulty level'
        ]);



        CRUD::addField([
            'name' => 'mode_of_delivery',
            'label' => 'Mode of Delivery',
            'type' => 'select_from_array',
            'options' => [
                'Online' => 'Online',
                'In Person' => 'In Person',
            ],
            'allows_null' => false,
            'wrapper' => ['class' => 'form-group col-6'],
            // 'hint' => 'Select the course difficulty level'
        ]);

        CRUD::addField([
            'name' => 'provider',
            'label' => 'Provider',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
            'hint' => 'eg Google, Microsoft, Coursera, etc.'
        ]);



        CRUD::addField([
            'name' => 'job_responsible',
            'label' => 'Job Responsible',
            'type' => 'textarea',
            // 'wrapper' => ['class' => 'form-group col-6'],
            'hint' => 'eg. Provide technical support with a focus on networking. Assist in network device management.'
        ]);



        CRUD::addField([
            'name' => 'course_modules',
            'label' => 'Course Modules',
            'type' => 'repeatable',
            'fields' => [
                [
                    'name' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                    'wrapper' => ['class' => 'form-group col-4'],
                    'hint' => 'eg. Power BI Basics - Interface, data import, simple visuals'
                ],
                [
                    'name' => 'description',
                    'type' => 'text',
                    'label' => 'Description',
                    'wrapper' => ['class' => 'form-group col-4'],
                ],
                [
                    'name' => 'status',
                    'type' => 'boolean',
                    'label' => 'Active?',
                    'default' => true,
                    'wrapper' => ['class' => 'form-group col-4'],
                ],
            ],
            'new_item_label' => 'Add Module',
            'init_rows' => 0,
            'value' => $courseModules,
        ]);



        CRUD::addField([
            'name' => 'course_certification',
            'label' => 'Course Certification',
            'type' => 'repeatable',
            'fields' => [
                [
                    'name' => 'title',
                    'type' => 'text',
                    'label' => 'Title',
                    'wrapper' => ['class' => 'form-group col-6'],
                    'hint' => 'eg. Microsoft PL-300: Power BI Data Analyst Associate'
                ],
                [
                    'name' => 'type',
                    'type' => 'text',
                    'label' => 'Type',
                    'wrapper' => ['class' => 'form-group col-6'],
                    'hint' => 'eg. International Certification'
                ],
                [
                    'name' => 'status',
                    'type' => 'boolean',
                    'label' => 'Active?',
                    'default' => true,
                    'wrapper' => ['class' => 'form-group col-6'],
                ],
                [
                    'name' => 'description',
                    'type' => 'textarea',
                    'label' => 'Description',
                    'wrapper' => ['class' => 'form-group col-6'],
                    'hint' => 'eg. Industry-recognized certification that validates your skills and expertise.'
                ],

            ],
            'new_item_label' => 'Add Course Certificate',
            'init_rows' => 0,
            'value' => $courseCertification,
        ]);

        CRUD::addField([
            'name' => 'prerequisites',
            'label' => 'Entry Requirements',
            'type' => 'textarea',
            // 'wrapper' => ['class' => 'form-group col-6'],
            'hint' => 'eg. 1. Minimum of a Masters degree in law, IT, data management, cybersecurity, business administration, or related fields.'
        ]);

        $this->addIsActiveField([true => 'True', false => 'False'], 'Status', 'status');

        $this->addOverviewField();

        $this->addTagsField(Programme::class, 'programmeTags', 'Tags');

        // get the number of course matches
        // $courseMatches = CourseMatch::all();
        // $tagFieldNames = [];
        // foreach ($courseMatches as $courseMatch) {
        //     $name = 'course_match_' . $courseMatch->id;
        //     $tagFieldNames[] = $name;
        //     CRUD::addField([
        //         'name' => $name,
        //         'label' => $courseMatch->question,
        //         'type'      => 'select2_multiple',
        //         'entity' => 'tags',
        //         'attribute' => 'answer',
        //         'model' => CourseMatchOption::class,
        //         'allows_null' => true,
        //         'options' => function ($query) use ($courseMatch) {
        //             return $query->where('course_match_id', $courseMatch->id)->get();
        //         },
        //         // 'wrapper' => ['class' => 'form-group col-6'],
        //     ]);
        // }

        $courseMatches = CourseMatch::where('status', 1)->get();
        $tagFieldNames = [];

        $programme = $this->crud->getCurrentEntry();

        foreach ($courseMatches as $courseMatch) {

            $fieldName = 'course_match_' . $courseMatch->id;
            $tagFieldNames[] = $fieldName;

            $selectedValues = [];

            if ($programme) {
                $selectedValues = $programme->tags()
                    ->where('course_match_id', $courseMatch->id)
                    ->pluck('course_match_option_id')
                    ->toArray();
            }

            CRUD::addField([
                'name' => $fieldName,
                'label' => $courseMatch->question,
                'type' => 'select2_multiple',

                'fake' => true, 
                'value' => $selectedValues,

                'model' => CourseMatchOption::class,
                'attribute' => 'answer',
                'allows_null' => true,

                'options' => function ($query) use ($courseMatch) {
                    return $query->where('course_match_id', $courseMatch->id)->get();
                },
            ]);
        }




        $this->addFieldsToTab('Info', true, ['title', 'sub_title', 'image', 'start_date', 'end_date', 'duration', 'course_category_id', 'status', 'level', 'mode_of_delivery', 'provider', 'job_responsible']);
        $this->addFieldsToTab('Module', true, ['course_modules']);
        $this->addFieldsToTab('Certification', true, ['course_certification']);
        $this->addFieldsToTab('Prerequisites', true, ['prerequisites']);
        $this->addFieldsToTab('Overview', true, ['overview']);
        $this->addFieldsToTab('Tags', true, $tagFieldNames);
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
