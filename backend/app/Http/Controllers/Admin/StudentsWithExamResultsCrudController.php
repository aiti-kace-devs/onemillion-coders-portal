<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\User;
use Illuminate\Support\Facades\View;

/**
 * Controller for Students with Exam Results
 * Extends UserCrudController to inherit common functionality
 */
class StudentsWithExamResultsCrudController extends UserCrudController
{
    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        parent::setup();

        CRUD::setRoute(config('backpack.base.route_prefix') . '/students-with-exam-results');
        CRUD::setEntityNameStrings('student with exam results', 'students with exam results');
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
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/students-with-exam-results');
        $this->crud->setEntityNameStrings('student with exam results', 'students with exam results');

        // Filter students who have exam results
        CRUD::setQuery(User::query()->whereHas('examResults'));

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

        // Add a custom column to show exam results count
        CRUD::addColumn([
            'name' => 'exam_results_count',
            'label' => 'Exam Results',
            'type' => 'text',
            'value' => function ($entry) {
                $resultsCount = $entry->examResults()->count();
                return $resultsCount . ' result(s)';
            },
        ]);

        // Add a column to show latest exam result
        CRUD::addColumn([
            'name' => 'latest_exam_result',
            'label' => 'Latest Result',
            'type' => 'text',
            'value' => function ($entry) {
                $latestResult = $entry->examResults()->latest()->first();
                if ($latestResult) {
                    return round(($latestResult->yes_ans / 30) * 100) . '%';
                }
                return 'N/A';
            },
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
            },
        ]);

        // Add a column to show if student has taken multiple exams
        CRUD::addColumn([
            'name' => 'exams_taken',
            'label' => 'Exams Taken',
            'type' => 'text',
            'value' => function ($entry) {
                $examsTaken = $entry->userExams()->count();
                return $examsTaken > 0 ? $examsTaken . ' exam(s)' : 'No exams taken';
            },
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
            },
        ]);

        // Add row actions using the custom dropdown
        \App\Helpers\CrudListHelper::editInDropdown([
            'crud::buttons.shortlist_row_actions_dropdown',
            'crud::buttons.view_results',
            'crud::buttons.reset_result'
        ]);

        // Enable bulk operations
        CRUD::enableBulkActions();

        // Add export options
        CRUD::enableExportButtons();
    }
}
