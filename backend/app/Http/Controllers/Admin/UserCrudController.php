<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UserRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\UserFieldHelpers;
use App\Helpers\WidgetHelper;
use App\Helpers\FilterHelper;
use App\Models\Course;
/**
 * Class UserCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserCrudController extends CrudController
{
    use \App\SearchableCRUD;
    use UserFieldHelpers;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\Pro\Http\Controllers\Operations\CustomViewOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user');
        CRUD::setEntityNameStrings('student', 'students');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->setupStudentColumns();
        // Disable responsive table
        // CRUD::disableResponsiveTable();
        FilterHelper::addBooleanFilter('shortlist', 'Shortlist');
        // Add export options
        CRUD::enableExportButtons();
        // Add custom views
        $this->runCustomViews([
            'setupStudentsWithAdmissionView' => 'Students with Admission',
            'setupStudentsWithoutExamResultsView' => 'Students without Exam Results',
            'setupStudentsYetToAcceptAdmissionView' => 'Students Yet to Accept Admission',
            'setupStudentsWithExamResultsView' => 'Students with Exam Results',
            'setupShortlistedStudentsView' => 'Shortlisted Students',
        ]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(UserRequest::class);
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

    /**
     * Custom view for students with admission
     */
    public function setupStudentsWithAdmissionView()
    {
        // Filter students who have admission records with session
        CRUD::setQuery(\App\Models\User::whereHas('admissions', function ($query) {
            $query->whereNotNull('session');
        }));

        // Add a custom column to show admission status
        CRUD::addColumn([
            'name' => 'admission_status',
            'label' => 'Admission Status',
            'type' => 'text',
            'value' => function ($entry) {
                $admission = $entry->admissions()->whereNotNull('session')->first();
                return $admission ? 'Admitted' : 'Not Admitted';
            }
        ]);

        // Add admission date column
        CRUD::addColumn([
            'name' => 'admission_date',
            'label' => 'Admission Date',
            'type' => 'date',
            'value' => function ($entry) {
                $admission = $entry->admissions()->whereNotNull('session')->first();
                return $admission ? $admission->created_at : null;
            }
        ]);

        // Add course column
        CRUD::addColumn([
            'name' => 'admitted_course',
            'label' => 'Admitted Course',
            'type' => 'text',
            'value' => function ($entry) {
                $admission = $entry->admissions()->whereNotNull('session')->first();
                if ($admission && $admission->course) {
                    return $admission->course->course_name ?? 'N/A';
                }
                return 'N/A';
            }
        ]);

        // Add exam score column
        CRUD::addColumn([
            'name' => 'exam_score',
            'label' => 'Exam Score',
            'type' => 'text',
            'value' => function ($entry) {
                $latestResult = $entry->examResults()->latest()->first();
                if ($latestResult) {
                    return $latestResult->result . '%';
                }
                return 'N/A';
            }
        ]);
    }

    /**
     * Custom view for students without exam results
     */
    public function setupStudentsWithoutExamResultsView()
    {
        // Filter students who don't have exam results
        CRUD::setQuery(\App\Models\User::whereDoesntHave('examResults'));

        // Add a custom column to show exam status
        CRUD::addColumn([
            'name' => 'exam_status',
            'label' => 'Exam Status',
            'type' => 'text',
            'value' => function ($entry) {
                return 'No Exam Results';
            }
        ]);

        // Add a column to show if student has taken exams
        CRUD::addColumn([
            'name' => 'exams_taken',
            'label' => 'Exams Taken',
            'type' => 'text',
            'value' => function ($entry) {
                $examsTaken = $entry->userExams()->count();
                return $examsTaken > 0 ? $examsTaken . ' exam(s)' : 'No exams taken';
            }
        ]);

        // Add a column to show if student has submitted exams
        CRUD::addColumn([
            'name' => 'submitted_exams',
            'label' => 'Submitted Exams',
            'type' => 'text',
            'value' => function ($entry) {
                $submittedExams = $entry->userExams()->whereNotNull('submitted')->count();
                return $submittedExams > 0 ? $submittedExams . ' submitted' : 'No submitted exams';
            }
        ]);
    }

    /**
     * Custom view for students yet to accept admission (session_id is null)
     */
    public function setupStudentsYetToAcceptAdmissionView()
    {
        // Filter students who have admission records but session_id is null
        CRUD::setQuery(\App\Models\User::whereHas('admissions', function ($query) {
            $query->whereNull('session');
        }));

        // Add a custom column to show admission status
        CRUD::addColumn([
            'name' => 'admission_status',
            'label' => 'Admission Status',
            'type' => 'text',
            'value' => function ($entry) {
                $admission = $entry->admissions()->whereNull('session')->first();
                return $admission ? 'Pending Acceptance' : 'No Admission';
            }
        ]);

        // Add admission date column
        CRUD::addColumn([
            'name' => 'admission_date',
            'label' => 'Admission Date',
            'type' => 'date',
            'value' => function ($entry) {
                $admission = $entry->admissions()->whereNull('session')->first();
                return $admission ? $admission->created_at : null;
            }
        ]);

        // Add course column
        CRUD::addColumn([
            'name' => 'offered_course',
            'label' => 'Offered Course',
            'type' => 'text',
            'value' => function ($entry) {
                $admission = $entry->admissions()->whereNull('session')->first();
                if ($admission && $admission->course) {
                    return $admission->course->course_name ?? 'N/A';
                }
                return 'N/A';
            }
        ]);

        // Add email sent status
        CRUD::addColumn([
            'name' => 'email_sent',
            'label' => 'Email Sent',
            'type' => 'text',
            'value' => function ($entry) {
                $admission = $entry->admissions()->whereNull('session')->first();
                return $admission && $admission->email_sent ? 'Yes' : 'No';
            }
        ]);

        // Add exam score column
        CRUD::addColumn([
            'name' => 'exam_score',
            'label' => 'Exam Score',
            'type' => 'text',
            'value' => function ($entry) {
                $latestResult = $entry->examResults()->latest()->first();
                if ($latestResult) {
                    return $latestResult->result . '%';
                }
                return 'N/A';
            }
        ]);
    }

    /**
     * Custom view for students with exam results
     */
    public function setupStudentsWithExamResultsView()
    {
        // Filter students who have exam results
        CRUD::setQuery(\App\Models\User::whereHas('examResults'));

        // Add a custom column to show exam results count
        CRUD::addColumn([
            'name' => 'exam_results_count',
            'label' => 'Exam Results',
            'type' => 'text',
            'value' => function ($entry) {
                $resultsCount = $entry->examResults()->count();
                return $resultsCount . ' result(s)';
            }
        ]);

        // Add a column to show latest exam result
        CRUD::addColumn([
            'name' => 'latest_exam_result',
            'label' => 'Latest Result',
            'type' => 'text',
            'value' => function ($entry) {
                $latestResult = $entry->examResults()->latest()->first();
                if ($latestResult) {
                    return $latestResult->result . '%';
                }
                return 'N/A';
            }
        ]);

        // Add a column to show exam name
        CRUD::addColumn([
            'name' => 'exam_name',
            'label' => 'Exam Name',
            'type' => 'text',
            'value' => function ($entry) {
                $latestResult = $entry->examResults()->with('exam')->latest()->first();
                if ($latestResult && $latestResult->exam) {
                    return $latestResult->exam->title ?? 'N/A';
                }
                return 'N/A';
            }
        ]);

        // Add a column to show if student has taken multiple exams
        CRUD::addColumn([
            'name' => 'exams_taken',
            'label' => 'Exams Taken',
            'type' => 'text',
            'value' => function ($entry) {
                $examsTaken = $entry->userExams()->count();
                return $examsTaken > 0 ? $examsTaken . ' exam(s)' : 'No exams taken';
            }
        ]);

        // Add exam score column
        CRUD::addColumn([
            'name' => 'exam_score',
            'label' => 'Exam Score',
            'type' => 'text',
            'value' => function ($entry) {
                $latestResult = $entry->examResults()->latest()->first();
                if ($latestResult) {
                    return $latestResult->result . '%';
                }
                return 'N/A';
            }
        ]);
    }

    /**
     * Custom view for shortlisted students
     */
    public function setupShortlistedStudentsView()
    {
        // Filter students who are shortlisted (shortlist = 1)
        CRUD::setQuery(\App\Models\User::where('shortlist', 1));

        // Add a custom column to show shortlist status
        CRUD::addColumn([
            'name' => 'shortlist_status',
            'label' => 'Shortlist Status',
            'type' => 'text',
            'value' => function ($entry) {
                return $entry->shortlist ? 'Shortlisted' : 'Not Shortlisted';
            }
        ]);

        // Add exam score column
        CRUD::addColumn([
            'name' => 'exam_score',
            'label' => 'Exam Score',
            'type' => 'text',
            'value' => function ($entry) {
                $latestResult = $entry->examResults()->latest()->first();
                if ($latestResult) {
                    return $latestResult->result . '%';
                }
                return 'N/A';
            }
        ]);

        // Add admission status column
        CRUD::addColumn([
            'name' => 'admission_status',
            'label' => 'Admission Status',
            'type' => 'text',
            'value' => function ($entry) {
                $admission = $entry->admissions()->whereNotNull('session')->first();
                if ($admission) {
                    return 'Admitted';
                }
                $pendingAdmission = $entry->admissions()->whereNull('session')->first();
                if ($pendingAdmission) {
                    return 'Pending Acceptance';
                }
                return 'Not Admitted';
            }
        ]);

        // Add course column
        CRUD::addColumn([
            'name' => 'registered_course',
            'label' => 'Registered Course',
            'type' => 'text',
            'value' => function ($entry) {
                if ($entry->registered_course && $entry->course) {
                    return $entry->course->course_name ?? 'N/A';
                }
                return 'N/A';
            }
        ]);

        // Add exams taken column
        CRUD::addColumn([
            'name' => 'exams_taken',
            'label' => 'Exams Taken',
            'type' => 'text',
            'value' => function ($entry) {
                $examsTaken = $entry->userExams()->count();
                return $examsTaken > 0 ? $examsTaken . ' exam(s)' : 'No exams taken';
            }
        ]);

        // Add Backpack-style bulk admit operation
        // CRUD::addBulkAction([
        //     'label' => 'Admit',
        //     'name' => 'admit',
        //     'icon' => 'la la-user-check',
        //     'callback' => function ($entries) {
        //         // This will be handled via AJAX/modal, so leave empty
        //     },
        //     'visible' => true, // always show in this view
        // ]);

        // Set custom view to include modal
        // $this->crud->setListView('vendor.backpack.crud.list_with_modal');
    }

    /**
     * Handle bulk admit operation via AJAX
     */
    public function bulkAdmit()
    {
        $request = request();
        $studentIds = $request->input('student_ids', []);
        $courseId = $request->input('course_id');
        $sessionId = $request->input('session_id');

        if (empty($studentIds) || !$courseId || !$sessionId) {
            return response()->json([
                'success' => false,
                'message' => 'Please select students, course, and session.'
            ], 400);
        }

        try {
            $students = \App\Models\User::whereIn('id', $studentIds)->get();
            $admittedCount = 0;

            foreach ($students as $student) {
                // Check if student already has an admission for this course/session
                $existingAdmission = $student->admissions()
                    ->where('course_id', $courseId)
                    ->where('session', $sessionId)
                    ->first();

                if (!$existingAdmission) {
                    // Create new admission
                    $student->admissions()->create([
                        'course_id' => $courseId,
                        'session' => $sessionId,
                        'confirmed' => now(),
                    ]);
                    $admittedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully admitted {$admittedCount} student(s)."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to admit students: ' . $e->getMessage()
            ], 500);
        }
    }
}
