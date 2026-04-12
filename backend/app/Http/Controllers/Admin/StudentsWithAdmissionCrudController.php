<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\User;
use Illuminate\Support\Facades\View;

/**
 * Controller for Students with Admission
 * Extends UserCrudController to inherit common functionality
 */
class StudentsWithAdmissionCrudController extends UserCrudController
{
    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        parent::setup();

        CRUD::setRoute(config('backpack.base.route_prefix') . '/students-with-admission');
        CRUD::setEntityNameStrings('student with admission', 'students with admission');
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
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/students-with-admission');
        $this->crud->setEntityNameStrings('student with admission', 'students with admission');

        // Set query to filter students with admission
        CRUD::setQuery(
            User::query()->whereHas('admissions', function ($query) {
                $query->whereNotNull('session');
            })
        );

        // Setup filters
        $this->setupFilter();

        // Select all columns needed for relationships & custom columns
        $this->crud->query->select([
            'id',
            'userId',
            'registered_course',
            'shortlist',
            'name',
            'gender',
            'age',
            'email',
            'mobile_no',
            'ghcard',
        ]);
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
                $admission = $entry->admissions()->whereNotNull('session')->first();
                return $admission ? 'Admitted' : 'Not Admitted';
            },
        ]);

        // Add admission date column
        CRUD::addColumn([
            'name' => 'admission_date',
            'label' => 'Admission Date',
            'type' => 'date',
            'value' => function ($entry) {
                $admission = $entry->admissions()->whereNotNull('session')->first();
                return $admission ? $admission->created_at : null;
            },
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
                return 'Not Found';
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

        // Add row actions using the custom dropdown
        \App\Helpers\CrudListHelper::editInDropdown(['crud::buttons.shortlist_row_actions_dropdown']);

        // Enable bulk operations
        CRUD::enableBulkActions();

        // Add export options
        CRUD::enableExportButtons();
    }
}
