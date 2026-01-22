<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\User;
use Illuminate\Support\Facades\View;

/**
 * Controller for Students Yet to Accept Admission
 * Extends UserCrudController to inherit common functionality
 */
class StudentsYetToAcceptAdmissionCrudController extends UserCrudController
{
    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        parent::setup();

        CRUD::setRoute(config('backpack.base.route_prefix') . '/students-yet-to-accept-admission');
        CRUD::setEntityNameStrings('student yet to accept admission', 'students yet to accept admission');
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
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/students-yet-to-accept-admission');
        $this->crud->setEntityNameStrings('student yet to accept admission', 'students yet to accept admission');

        // Filter students who have admission records but session_id is null
        CRUD::setQuery(
            User::query()->whereHas('admissions', function ($query) {
                $query->whereNull('session');
            })
        );

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

        // Add a custom column to show admission status
        CRUD::addColumn([
            'name' => 'admission_status',
            'label' => 'Admission Status',
            'type' => 'text',
            'value' => function ($entry) {
                $admission = $entry->admissions()->whereNull('session')->first();
                return $admission ? 'Pending Acceptance' : 'No Admission';
            },
        ]);

        // Add admission date column
        CRUD::addColumn([
            'name' => 'admission_date',
            'label' => 'Admission Date',
            'type' => 'date',
            'value' => function ($entry) {
                $admission = $entry->admissions()->whereNull('session')->first();
                return $admission ? $admission->created_at : null;
            },
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
            },
        ]);

        // Add email sent status
        CRUD::addColumn([
            'name' => 'email_sent',
            'label' => 'Email Sent',
            'type' => 'text',
            'value' => function ($entry) {
                $admission = $entry->admissions()->whereNull('session')->first();
                return $admission && $admission->email_sent ? 'Yes' : 'No';
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
                    return round(($latestResult->yes_ans / 30) * 100) . '%';
                }
                return 'N/A';
            },
        ]);

        // Enable bulk operations
        CRUD::enableBulkActions();

        // Add export options
        CRUD::enableExportButtons();
    }
}
