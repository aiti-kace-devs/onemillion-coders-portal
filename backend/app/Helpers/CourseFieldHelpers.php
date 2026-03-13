<?php

namespace App\Helpers;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\FilterHelper;
use App\Helpers\GeneralFieldsAndColumns;
use App\Models\Centre;
use App\Models\Programme;
use App\Models\Branch;
use App\Models\Batch;
use App\Models\Course;
use App\Models\UserAdmission;
use App\Models\CourseMatch;

trait CourseFieldHelpers
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
            'type' => 'select2',
            'model' => Branch::class,
            'attribute' => 'title',
            'allows_null' => true,
            'wrapper' => ['class' => 'form-group col-12'],
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
    'value' => optional($this->crud->getCurrentEntry())->centre_id ?? null,
]);



        // CRUD::addField([
        //     'name' => 'batch_id',
        //     'label' => 'Select Batch',
        //     'type' => 'select2',
        //     'entity' => 'batch',
        //     'attribute' => 'title',
        //     'model' => Batch::class,
        //     'allows_null' => false,
        //     'wrapper' => ['class' => 'form-group col-6'],
        // ]);



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

        $this->addTagsField(Course::class);


        CRUD::addField([
            'name' => 'duration',
            'label' => 'Duration',
            'type' => 'text',
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
        //     'wrapper' => ['class' => 'form-group col-6'],
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

        $this->addIsActiveField([true => 'True', false => 'False'], 'Status', 'status');

        $this->addFieldsToTab('Course Info', true, ['branch_id', 'centre_id', 'programme_id','tags', 'status']);
        $this->addFieldsToTab('Course Duration', true, ['duration', 'start_date', 'end_date']);
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
            'type' => 'number',
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
                'Fullday' => 'Fullday',
            ],
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        CRUD::addField([
            'name' => 'course_time',
            'label' => 'Duration',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
            'hint' => 'eg. 8am - 1pm'
        ]);

        $this->addIsActiveField([true => 'True', false => 'False'], 'Status', 'status');


        CRUD::addField([
            'name' => 'link',
            'label' => 'Link',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
            'hint' => 'eg. https://chat.whatsapp.com/BekTu3PWEqc8UtydifN8Mt'
        ]);
    }






    protected function courseMatchOptionsFields()
    {

        CRUD::addField([
            'name' => 'answer',
            'label' => 'Answer',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-5'],
        ]);

        CRUD::field([
            'name' => 'value',
            'target' => 'answer',
            'label' => "Option Tag",
            'type' => 'slug',
            'locale' => 'pt',
            'separator' => '',
            'trim' => true,
            'lower' => true,
            'strict' => true,
            'remove' => '/[*+~.()!:@]/g',
            'wrapper' => ['class' => 'form-group col-5'],
        ])->attributes(['readonly' => 'readonly']);


        CRUD::addField([
            'name' => 'icon',
            'type' => 'icon_picker',
            'label' => 'Icon',
            'iconset' => 'fontawesome',
            'wrapper' => ['class' => 'form-group col-2'],
        ]);

        CRUD::addField([
            'name' => 'course_match_id',
            'label' => 'Course Match',
            'type' => 'select2',
            'entity' => 'courseMatch',
            'attribute' => 'question',
            'model' => CourseMatch::class,
            'allows_null' => false,
            'wrapper' => ['class' => 'form-group col-6'],
        ]);


        CRUD::addField([
            'name' => 'description',
            'label' => 'Description',
            'type' => 'textarea',
            'wrapper' => ['class' => 'form-group col-6'],
        ]);

        $this->addIsActiveField([true => 'True', false => 'False'], 'Status', 'status');
    }




    public function addCourseField(): void
    {
        CRUD::addColumn([
            'name' => 'course',
            'label' => 'Course',
            'type' => 'closure',
            'function' => function ($entry) {
                if ($entry->course) {
                    $url = backpack_url('course/' . $entry->course->id . '/show');
                    return '<a href="' . $url . '">' . e($entry->course->course_name) . '</a>';
                }
                return '-';
            },
            'escaped' => false,
        ]);
    }




    public static function courseFilter(string $columnName, string $label = 'Course'): void
    {
        $coursesArray = Course::orderBy('course_name')->pluck('course_name', 'id')->toArray();

        FilterHelper::addSelectFilter(
            columnName: $columnName,
            label: $label,
            options: $coursesArray,
            type: 'select2_multiple',
            callback: function ($value) use ($columnName) {
                // Convert string to array if it's not already
                $values = is_array($value) ? $value : explode(',', $value);

                // Use whereIn for multiple values
                CRUD::addClause('whereIn', $columnName, $values);
            },
        );
    }


    public static function addProgrammeFilter(string $columnName, string $label = 'Course Filter'): void
    {
        $coursesArray = Programme::orderBy('title')->pluck('title', 'id')->toArray();
        FilterHelper::addSelectFilter(
            columnName: $columnName,
            label: $label,
            options: $coursesArray,
            callback: function ($value) use ($columnName) {
                CRUD::addClause('where', $columnName, $value);
            }
        );
    }




    public static function addBatchFilter(string $columnName, string $label = 'Batch Filter'): void
    {
        $BatchsArray = Batch::orderBy('title')->pluck('title', 'id')->toArray();
        FilterHelper::addSelectFilter(
            columnName: $columnName,
            label: $label,
            options: $BatchsArray,
            callback: function ($value) use ($columnName) {
                CRUD::addClause('where', $columnName, $value);
            }
        );
    }



    // public static function addStudentBatchFilter(string $label = 'Batch Filter'): void
    // {
    //     $batches = Batch::orderBy('title')->pluck('title', 'id')->toArray();

    //     FilterHelper::addSelectFilter(
    //         columnName: 'batch_id',
    //         label: 'Batch Filter',
    //         options: $batches,
    //         type: 'select2',
    //         callback: function ($batchId) {
    //             CRUD::addClause('whereHas', 'course', function ($query) use ($batchId) {
    //                 $query->where('batch_id', $batchId);
    //             });
    //         },
    //     );
    // }


    public static function addStudentBatchFilter(string $label = 'Batch')
    {
        $batches = Batch::orderBy('title')->pluck('title', 'id')->toArray();

        FilterHelper::addSelectFilter(
            columnName: 'student_batch_filter',
            label: $label,
            options: $batches,
            type: 'select2',
            callback: function ($batchId) {
                static::addBatchWhereClause($batchId);
            },
        );
    }

    protected static function addBatchWhereClause($batchId)
    {
        CRUD::addClause('whereHas', 'course.batches', function ($query) use ($batchId) {
            $query->where('course_batches.id', $batchId);
        });
    }









    public static function addStudentBatchFilterFromDashboard(string $relationPath = 'admission', string $label = 'Admitted Student Batch')
    {
        $batches = Batch::orderBy('title')->pluck('title', 'id')->toArray();

        FilterHelper::addSelectFilter(
            columnName: 'batch_filter',
            label: $label,
            options: $batches,
            type: 'select2',
            callback: function ($batchId) use ($relationPath) {
                static::addBatchWhereClauseFromDashboard($batchId, $relationPath);
            },
        );
    }

    protected static function addBatchWhereClauseFromDashboard($batchId, $relationPath)
    {
        CRUD::addClause('whereHas', $relationPath, function ($query) use ($batchId) {
            $query->where('batch_id', $batchId);
        });
    }




    public static function addConfirmedAdmissionFilter(string $label = 'Admission')
    {
        CRUD::addFilter(
            [
                'name' => 'confirmed_admission',
                'type' => 'dropdown',
                'label' => $label,
            ],
            [
                1 => 'Admitted',
                0 => 'Not Admitted',
            ],
            function ($value) {
                if ($value == 1) {
                    CRUD::addClause('whereExists', function ($query) {
                        $query->select(\DB::raw(1))
                            ->from('user_admission')
                            ->whereColumn('user_admission.user_id', 'users.userId')
                            ->whereNotNull('user_admission.confirmed')
                            ->groupBy('user_admission.user_id')
                            ->havingRaw('COUNT(*) = 1');
                    });
                } elseif ($value == 0) {
                    CRUD::addClause('whereNotExists', function ($query) {
                        $query->select(\DB::raw(1))
                            ->from('user_admission')
                            ->whereColumn('user_admission.user_id', 'users.userId')
                            ->whereNotNull('user_admission.confirmed');
                    });
                }
            }
        );
    }




    public static function addAdmittedAtFilter(string $label = 'Admitted At')
    {
        CRUD::addFilter(
            [
                'name' => 'admitted_at',
                'type' => 'date_range',
                'label' => $label,
                'date_range_options' => [
                    'format' => 'YYYY-MM-DD',
                    'showDropdowns' => true,
                ]
            ],
            false,
            function ($value) {
                $dates = json_decode($value);

                CRUD::addClause('whereExists', function ($query) use ($dates) {
                    $query->select(\DB::raw(1))
                        ->from('user_admission')
                        ->whereColumn('user_admission.user_id', 'users.userId')
                        ->whereNotNull('user_admission.confirmed')
                        ->whereBetween('user_admission.confirmed', [
                            $dates->from,
                            $dates->to,
                        ]);
                });
            }
        );
    }


    protected static function getAdmissionLocations(): array
    {
        return UserAdmission::query()
            ->whereNotNull('location')
            ->distinct()
            ->pluck('location', 'location')
            ->toArray();
    }



    public static function centreFilter(string $label = 'Centre')
    {
        FilterHelper::addSelectFilter(
            columnName: 'admission_location',
            label: $label,
            options: self::getAdmissionLocations(),
            type: 'select2_multiple',
            callback: function (array $values) {
                if (empty($values)) {
                    return;
                }

                CRUD::addClause('whereExists', function ($query) use ($values) {
                    $query->select(\DB::raw(1))
                        ->from('user_admission')
                        ->whereColumn('user_admission.user_id', 'users.userId')
                        ->whereIn('user_admission.location', $values);
                });
            }
        );
    }





    public static function addConfirmedAdmissionColumn(string $label = 'Admitted')
    {
        CRUD::addColumn([
            'name' => 'confirmed_admission',
            'label' => $label,
            'type' => 'closure',
            'function' => function ($entry) {
                $count = UserAdmission::where('user_id', $entry->userId)
                    ->whereNotNull('confirmed')
                    ->count();

                if ($count === 1) {
                    return '<span>✅</span>';
                }
                return '<span class="badge bg-danger">No</span>';
            },
            'escaped' => false,
        ]);
    }





    protected function courseMatchFields()
    {
        $typeOptions = [
            'General' => 'General',
            'Choice' => 'Choice',
        ];

        $referenceOptions = [
            'course_categories' => 'Course Categories (auto)',
            'branches' => 'Branches (auto)',
            'mode_of_delivery' => 'Mode of Delivery (auto)',
        ];

        CRUD::addField([
            'name' => 'tag',
            'label' => 'Tag',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-4'],
        ]);

        CRUD::addField([
            'name' => 'question',
            'label' => 'Question',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-8'],
        ]);

        CRUD::addField([
            'name' => 'type',
            'label' => 'Type',
            'type' => 'select_from_array',
            'options' => $typeOptions,
            'allows_null' => false,
            'wrapper' => ['class' => 'form-group col-4'],
        ]);

        CRUD::addField([
            'name' => 'reference_source',
            'label' => 'Reference Options',
            'type' => 'select_from_array',
            'options' => $referenceOptions,
            'allows_null' => true,
            'wrapper' => ['class' => 'form-group col-4'],
            'hint' => 'Leave blank for manual options. When set, options are auto-synced from the reference source.',
        ]);

        CRUD::addField([
            'name' => 'order',
            'label' => 'Order',
            'type' => 'number',
            'wrapper' => ['class' => 'form-group col-4'],
        ]);


        CRUD::addField([
            'name' => 'description',
            'label' => 'Description',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-12'],
        ]);


        // CRUD::addField([
        //     'name'    => 'icon',
        //     'type'    => 'icon_picker',
        //     'label'   => 'Icon',
        //     'iconset' => 'fontawesome',
        //     'wrapper' => ['class' => 'form-group col-2'],
        // ]);


 

        $this->addIsActiveField([true => 'True', false => 'False'], 'Multiple Select', 'is_multiple_select');
        $this->addIsActiveField([true => 'True', false => 'False'], 'Status', 'status');

        CRUD::addField([
            'name' => 'course_match_options',
            'label' => 'Options',
            'type' => 'repeatable',
            'new_item_label' => 'Add Option',
            'hint' => 'Manual options are ignored when Reference Options is set.',
            'fields' => [
                [
                    'name' => 'id',
                    'type' => 'hidden',
                ],
                [
                    'name' => 'answer',
                    'label' => 'Answer',
                    'type' => 'text',
                    'wrapper' => ['class' => 'form-group col-5'],
                ],
                [
                    'name' => 'value',
                    'label' => "Option Tag",
                    'type' => 'text',
                    'wrapper' => ['class' => 'form-group col-5'],
                ],
                [
                    'name' => 'icon',
                    'label' => 'Icon',
                    'type' => 'icon_picker',
                    'iconset' => 'fontawesome',
                    'wrapper' => ['class' => 'form-group col-2'],
                ],
                [
                    'name' => 'description',
                    'label' => 'Description',
                    'type' => 'textarea',
                ],
                [
                    'name' => 'status',
                    'type' => 'boolean',
                    'label' => 'Active?',
                    'default' => true,
                    'wrapper' => ['class' => 'form-group col-6'],
                ],
            ],
            'init_rows' => 1,
            'min_rows' => 1,
        ]);


        $this->addFieldsToTab('Question', true, ['tag', 'question', 'description', 'type', 'reference_source', 'order', 'is_multiple_select', 'status']);
        $this->addFieldsToTab('Options', true, ['course_match_options']);
    }
}
