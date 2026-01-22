<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\User;
use Illuminate\Support\Facades\View;

/**
 * Controller for Shortlisted Students
 * Extends UserCrudController to inherit common functionality
 */
class ShortlistedStudentsCrudController extends UserCrudController
{
    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        parent::setup();

        CRUD::setRoute(config('backpack.base.route_prefix') . '/shortlisted-students');
        CRUD::setEntityNameStrings('shortlisted student', 'shortlisted students');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @return void
     */
    protected function setupListOperation()
    {
        // Check permissions
        if (!backpack_user()->can('student.read.all')) {
            abort(403, 'Unauthorized action.');
        }

        $this->crud->setModel(User::class);
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/shortlisted-students');
        $this->crud->setEntityNameStrings('shortlisted student', 'shortlisted students');

        // Filter students who are shortlisted (shortlist = 1)
        CRUD::setQuery(User::query()->where('shortlist', 1));

        $this->crud->query->with(['course']);

        // Setup filters
        $this->setupFilter();

        $this->crud->query->select(['id', 'name', 'gender', 'age', 'email', 'mobile_no', 'ghcard', 'userId', 'registered_course', 'shortlist']);
        $this->addConfirmedAdmissionColumn();

        View::share('mailable', \App\Helpers\MailerHelper::getMailableClasses());
        $this->setupStudentColumns();

        CRUD::removeButtonFromStack('bulk_actions_dropdown', 'top');
        CRUD::addButtonFromView('top', 'student_views_dropdown', 'student_views_dropdown', 'beginning');
        CRUD::addButtonFromView('top', 'bulk_shortlist_actions_dropdown', 'bulk_shortlist_actions_dropdown', 'beginning');

        // Add userId column
        CRUD::addColumn([
            'name' => 'userId',
            'label' => 'User ID',
            'type' => 'text',
        ]);

        // Add exam score column
        CRUD::addColumn([
            'name' => 'exam_score',
            'label' => 'Exam Score',
            'type' => 'text',
            'value' => function ($entry) {
                $latestResult = $entry->examResults()->latest()->first();
                if ($latestResult) {
                    return round(($latestResult->yes_ans / 30) * 100) . '%';
                }
                return 'N/A';
            },
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
            },
        ]);

        // Add course column
        // Add course column with debugging
        CRUD::addColumn([
            'name' => 'registered_course',
            'label' => 'Registered Course',
            'type' => 'closure',
            'value' => function ($entry) {
                if ($entry->registered_course) {
                    return $entry->course->course_name ?? 'Course Not Found';
                }
                return 'Not Registered';
            },
        ]);

        // Add exams taken column
        CRUD::addColumn([
            'name' => 'exams_taken',
            'label' => 'Exams Taken',
            'type' => 'text',
            'value' => function ($entry) {
                $examsTaken = $entry->userExams()->count();
                return $examsTaken > 0 ? $examsTaken . ' exam(s)' : 'No exams taken';
            },
        ]);

        // Remove default edit, preview, delete buttons and add custom row actions dropdown
        CRUD::removeButton('line', 'update');
        CRUD::removeButton('line', 'show');
        CRUD::removeButton('line', 'delete');
        CRUD::addButton('line', 'shortlist_row_actions_dropdown', 'view', 'crud::buttons.shortlist_row_actions_dropdown');

        // Enable bulk operations
        CRUD::enableBulkActions();

        // Add export options
        CRUD::enableExportButtons();
    }
}
