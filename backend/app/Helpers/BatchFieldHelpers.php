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

trait BatchFieldHelpers
{


    use FormHelper;
    use GeneralFieldsAndColumns;




    protected function setupCommonBatchListFields()
    {

        $this->crud->query
            ->select('admission_batches.*')
            ->addSelect('admission_batches.id')
            ->selectRaw('(
                SELECT COUNT(ua.id)
                FROM courses c
                LEFT JOIN user_admission ua ON ua.course_id = c.id AND ua.confirmed IS NOT NULL
                WHERE ua.batch_id = admission_batches.id
            ) AS admitted_students_count')
            ->selectRaw('(
                SELECT COUNT(cb.course_id)
                FROM course_batches cb
                WHERE cb.batch_id = admission_batches.id
            ) AS courses_count');


        CRUD::column('title')->type('text');
        CRUD::column('year');
        CRUD::column('start_date');
        CRUD::column('end_date');

        CRUD::addColumn([
            'name' => 'courses_count',
            'label' => 'Courses',
            'type' => 'closure',
            'function' => function ($entry) {
                $courseIds = $entry->courses()
                    ->pluck('id')
                    ->unique()
                    ->values()
                    ->toArray();

                $courseCount = count($courseIds);

                if ($courseCount > 0) {
                    $encodedIds = urlencode(json_encode($courseIds));
                    $url = url("/admin/course-batch?batch_id={$entry->id}&course_id={$encodedIds}");

                    return "<a href='{$url}'>{$courseCount}</a>";
                }

                return '';
            },
            'escaped' => false,
        ]);

        CRUD::addColumn([
            'name' => 'admitted_students_count',
            'label' => 'Admitted Students',
            'type' => 'closure',
            'function' => function ($entry) {
                $batchId = $entry->id;
                $admittedCount = $entry->admitted_students_count;

                if ($admittedCount > 0) {
                    $url = url("/admin/user?batch_id={$batchId}&confirmed_admission=1");
                    return "<a href='{$url}'>{$admittedCount}</a>";
                }

                return '';
            },
            'escaped' => false,
        ]);

    }


    protected function setupCommonBatchFields()
    {
        CRUD::addField([
            'name' => 'title',
            'label' => 'Title',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
            'hint' => 'eg. Quarter 1, Batch 1',
            'tab' => 'General Info',
        ]);

        CRUD::addField([
            'name' => 'description',
            'label' => 'Description',
            'type'      => 'text',
            'wrapper' => ['class' => 'form-group col-6'],
            'tab' => 'General Info',
        ]);

        CRUD::addField([
            'name' => 'start_date',
            'label' => 'Start Date',
            'type'      => 'date',
            'wrapper' => ['class' => 'form-group col-6'],
            'tab' => 'General Info',
        ]);

        CRUD::addField([
            'name' => 'end_date',
            'label' => 'End Date',
            'type'      => 'date',
            'wrapper' => ['class' => 'form-group col-6'],
            'tab' => 'General Info',
        ]);

        $this->addIsActiveField([true  => 'True', false => 'False'], 'Status', 'status', 'General Info');
        $this->addIsActiveField([true  => 'True', false => 'False'], 'Completed', 'completed', 'General Info');
        $this->addFieldsToTab('General Info', true, ['title', 'description', 'start_date', 'end_date', 'status', 'completed']);
    }






    protected function getAddCourseModalHtml($batch)
    {
        $branches = Branch::pluck('title', 'id')->toArray();
        $programmes = Programme::pluck('title', 'id')->toArray();
        
        // Use the blade view for the modal
        return view('admin.batch.add_course_modal', [
            'batch' => $batch,
            'branches' => $branches,
            'programmes' => $programmes,
        ])->render();
    }

    /**
     * Generate HTML for course actions
     */
    protected function getCoursesActionsHtml($batch)
    {
        $html = '<button type="button" class="btn btn-primary mb-3" onclick="openAddCourseModal()">
            <i class="la la-plus"></i> Add Course
        </button>

        <table class="table table-bordered table-striped mt-3">
            <thead>
                <tr>
                    <th>Course Name</th>
                    <th>Centre</th>
                    <th>Programme</th>
                    <th>Location</th>
                    <th>Duration</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($batch->courses as $course) {
            $editUrl = backpack_url('course/' . $course->id . '/edit');
            $deleteUrl = backpack_url('course/' . $course->id);
            
            $html .= '<tr>
                <td>' . e($course->course_name) . '</td>
                <td>' . e($course->centre?->title ?? '-') . '</td>
                <td>' . e($course->programme?->title ?? '-') . '</td>
                <td>' . e($course->location ?? '-') . '</td>
                <td>' . e($course->duration) . '</td>
                <td>' . e($course->start_date) . '</td>
                <td>' . e($course->end_date) . '</td>
                <td>' . e($course->status ?? '-') . '</td>
                <td>
                    <a href="' . $editUrl . '" class="btn btn-sm btn-link">
                        <i class="la la-eye"></i> View
                    </a>
                    <a href="' . $editUrl . '" class="btn btn-sm btn-link">
                        <i class="la la-edit"></i> Edit
                    </a>
                    <form action="' . $deleteUrl . '" method="POST" style="display:inline;">
                        ' . csrf_field() . method_field('DELETE') . '
                        <button type="submit" class="btn btn-sm btn-link text-danger" onclick="return confirm(\'Are you sure you want to delete this course?\')">
                            <i class="la la-trash"></i> Delete
                        </button>
                    </form>
                </td>
            </tr>';
        }

        $html .= '</tbody></table>';
        
        if ($batch->courses->isEmpty()) {
            $html .= '<p class="text-muted text-center py-4">No courses assigned to this batch yet.</p>';
        }

        return $html;
    }






    /**
     * Add courses to a batch from the modal form
     */
    public function addCourses($batchId)
    {
        // Check permissions
        if (!backpack_user()->can('batch.update.all')) {
            abort(403, 'Unauthorized action.');
        }

        $batch = Batch::findOrFail($batchId);

        $branchId = request()->input('branch_id');
        $centreIds = request()->input('centre_ids', []);
        $programmeIds = request()->input('programme_ids', []);
        $startDate = request()->input('start_date');
        $endDate = request()->input('end_date');

        // Validate required fields
        if (!$branchId || empty($centreIds) || empty($programmeIds)) {
            return redirect()
                ->back()
                ->with('error', 'Please select branch, at least one centre, and at least one programme.');
        }

        $createdCount = 0;

        // Create a course for each combination of programme and centre
        foreach ($programmeIds as $programmeId) {
            foreach ($centreIds as $centreId) {
                // Check if this combination already exists
                $existingCourse = Course::where('centre_id', $centreId)
                    ->where('programme_id', $programmeId)
                    ->where('batch_id', $batch->id)
                    ->first();

                if (!$existingCourse) {
                    $course = Course::create([
                        'centre_id' => $centreId,
                        'programme_id' => $programmeId,
                        'course_name' => null, // Will be auto-generated by the booted observer
                        'location' => null, // Will be auto-generated by the booted observer
                        'start_date' => $startDate ?: $batch->start_date,
                        'end_date' => $endDate ?: $batch->end_date,
                        'batch_id' => $batch->id,
                        'status' => true,
                    ]);

                    $createdCount++;
                }
            }
        }

        if ($createdCount > 0) {
            return redirect()
                ->back()
                ->with('success', "{$createdCount} course(s) added successfully.");
        } else {
            return redirect()
                ->back()
                ->with('info', 'No new courses were added. All selected combinations already exist.');
        }
    }



}
