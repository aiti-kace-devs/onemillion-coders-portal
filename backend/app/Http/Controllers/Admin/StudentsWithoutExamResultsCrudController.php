<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\User;
use Illuminate\Support\Facades\View;

/**
 * Controller for Students without Exam Results
 * Extends UserCrudController to inherit common functionality
 */
class StudentsWithoutExamResultsCrudController extends UserCrudController
{
    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        parent::setup();

        CRUD::setRoute(config('backpack.base.route_prefix') . '/students-without-exam-results');
        CRUD::setEntityNameStrings('student without exam results', 'students without exam results');
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
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/students-without-exam-results');
        $this->crud->setEntityNameStrings('student without exam results', 'students without exam results');

        // Filter students who don't have exam results
        CRUD::setQuery(User::query()->whereDoesntHave('examResults'));

        // Setup filters
        $this->setupFilter();

        $this->crud->query->select(['id','name', 'gender', 'age', 'email', 'mobile_no', 'ghcard', 'userId', 'registered_course', 'shortlist']);
        $this->addConfirmedAdmissionColumn();

        View::share('mailable', \App\Helpers\MailerHelper::getMailableClasses());
        $this->setupStudentColumns();

        CRUD::addButtonFromView('top', 'student_views_dropdown', 'student_views_dropdown', 'beginning');
        CRUD::addButtonFromView('top', 'bulk_actions_dropdown', 'bulk_actions_dropdown', 'beginning');
        CRUD::addButton('top', 'assign_batch_bulk', 'view', 'admin.bulk.assign_batch', 'beginning');

        // Add userId column
        CRUD::addColumn([
            'name' => 'userId',
            'label' => 'User ID',
            'type' => 'text',
        ]);

        // Add a custom column to show exam status
        CRUD::addColumn([
            'name' => 'exam_status',
            'label' => 'Exam Status',
            'type' => 'text',
            'value' => function ($entry) {
                return 'No Exam Results';
            },
        ]);

        // Add a column to show if student has taken exams
        CRUD::addColumn([
            'name' => 'exams_taken',
            'label' => 'Exams Taken',
            'type' => 'text',
            'value' => function ($entry) {
                $examsTaken = $entry->userExams()->count();
                return $examsTaken > 0 ? $examsTaken . ' exam(s)' : 'No exams taken';
            },
        ]);

        // Add a column to show if student has submitted exams
        CRUD::addColumn([
            'name' => 'submitted_exams',
            'label' => 'Submitted Exams',
            'type' => 'text',
            'value' => function ($entry) {
                $submittedExams = $entry->userExams()->whereNotNull('submitted')->count();
                return $submittedExams > 0 ? $submittedExams . ' submitted' : 'No submitted exams';
            },
        ]);

        // Enable bulk operations
        CRUD::enableBulkActions();

        // Add export options
        CRUD::enableExportButtons();
    }
}
