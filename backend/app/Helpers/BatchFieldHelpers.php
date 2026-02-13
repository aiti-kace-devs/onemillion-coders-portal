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
                SELECT COUNT(c2.id)
                FROM courses c2
                WHERE c2.batch_id = admission_batches.id
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
                $courseCount = (int) ($entry->courses_count ?? 0);
                if ($courseCount > 0) {
                    $url = backpack_url('batch/' . $entry->id . '/edit');
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
        $currentBatchId = optional($this->crud->getCurrentEntry())->id;

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

        // Prompt user if they try to activate a batch while another batch is already active.
        $activeBatches = Batch::query()
            ->select(['id', 'title', 'start_date', 'end_date'])
            ->where('status', true)
            ->when($currentBatchId, fn ($q) => $q->where('id', '!=', $currentBatchId))
            ->orderBy('start_date')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'title' => $b->title,
                'start_date' => $b->start_date,
                'end_date' => $b->end_date,
            ])
            ->values();

        if ($activeBatches->isNotEmpty()) {
            CRUD::addField([
                'name' => 'active_batch_prompt',
                'type' => 'custom_html',
                'value' => '<script>
(() => {
  "use strict";

  const activeBatches = ' . json_encode($activeBatches) . ';

  function findConflict() {
    return Array.isArray(activeBatches) && activeBatches.length ? activeBatches[0] : null;
  }

  function setStatusInactive(hiddenInput) {
    if (!hiddenInput) return;
    const checkbox = hiddenInput.nextElementSibling;
    hiddenInput.value = 0;
    if (checkbox && checkbox.type === "checkbox") {
      checkbox.checked = false;
    }
  }

  function showMessage(conflict) {
    const range = (conflict && conflict.start_date && conflict.end_date) ? ` (${conflict.start_date} to ${conflict.end_date})` : "";
    const text = `Another batch is already active: "${conflict.title}"${range}.\\n\\nOnly one active ongoing batch is allowed.`;
    if (window.swal) {
      window.swal("Active batch conflict", text, "warning");
    } else {
      alert(text);
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    const statusHidden = document.querySelector(\'input[type="hidden"][name="status"]\');
    if (!statusHidden) return;

    const maybeBlock = () => {
      if (parseInt(statusHidden.value, 10) !== 1) return;
      const completedHidden = document.querySelector(\'input[type="hidden"][name="completed"]\');
      if (completedHidden && parseInt(completedHidden.value, 10) === 1) {
        setStatusInactive(statusHidden);
        const text = "A completed batch cannot be active.";
        if (window.swal) {
          window.swal("Invalid status", text, "warning");
        } else {
          alert(text);
        }
        return;
      }
      const conflict = findConflict();
      if (!conflict) return;
      setStatusInactive(statusHidden);
      showMessage(conflict);
    };

    statusHidden.addEventListener("change", maybeBlock);

    const startInput = document.querySelector(\'input[name="start_date"]\');
    const endInput = document.querySelector(\'input[name="end_date"]\');
    if (startInput) startInput.addEventListener("change", maybeBlock);
    if (endInput) endInput.addEventListener("change", maybeBlock);
  }, { once: true });
})();
</script>',
                'wrapper' => false,
                'tab' => 'General Info',
            ]);
        }
    }






    protected function getAddCourseModalHtml($batch)
    {
        $branches = Branch::pluck('title', 'id')->toArray();
        $programmes = Programme::query()
            ->select(['id', 'title', 'start_date', 'end_date'])
            ->orderBy('title')
            ->get();
        
        // Use the blade view for the modal
        return view('admin.batch.add_course_modal', [
            'batch' => $batch,
            'branches' => $branches,
            'programmes' => $programmes,
        ]);
    }

    /**
     * Generate HTML for course actions
     */
    protected function getCoursesActionsHtml($batch)
    {
        $isEmpty = $batch->courses->isEmpty();

        $html = '<button type="button" class="btn btn-primary mb-3" onclick="openAddCourseModal()">
            <i class="la la-plus"></i> Add Course
        </button>';

        if (!$isEmpty) {
            $html .= '<div class="mb-3">
                <input type="search" id="batchCoursesSearch" class="form-control" placeholder="Search assigned courses..." autocomplete="off">
            </div>';
        }

        $html .= '
        <table id="batchCoursesTable" class="table table-bordered table-striped mt-3">
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
            <tbody id="batchCoursesTableBody">';

        $admittedCountsByCourseId = collect();
        $courseIds = $batch->courses->pluck('id')->filter()->values();
        if ($courseIds->isNotEmpty()) {
            $admittedCountsByCourseId = UserAdmission::query()
                ->whereIn('course_id', $courseIds->all())
                ->whereNotNull('confirmed')
                ->selectRaw('course_id, COUNT(*) as admitted_count')
                ->groupBy('course_id')
                ->pluck('admitted_count', 'course_id');
        }

        foreach ($batch->courses as $course) {
            $editUrl = backpack_url('course/' . $course->id . '/edit');
            $showUrl = backpack_url('course-batch/' . $course->id . '/show');
            $deleteUrl = backpack_url('course/' . $course->id);
            $updateUrl = backpack_url('batch/update-course/' . $course->id);
            $branchId = $course->centre?->branch_id;
            $admittedCount = (int) ($admittedCountsByCourseId[$course->id] ?? 0);

            $statusToggle = view('admin.status_toggle.status_column', [
                'entry' => $course,
                'crud' => $this->crud ?? null,
                'column' => [
                    'name' => 'status',
                    'toggle_url' => 'course-batch/{id}/toggle',
                    'toggle_success_message' => 'Course status updated successfully.',
                    'toggle_error_message' => 'Error updating course status.',
                ],
            ])->render();
            
            $html .= '<tr>
                <td>' . e($course->course_name) . '</td>
                <td>' . e($course->centre?->title ?? '-') . '</td>
                <td>' . e($course->programme?->title ?? '-') . '</td>
                <td>' . e($course->location ?? '-') . '</td>
                <td>' . e($course->duration) . '</td>
                <td>' . e($course->start_date) . '</td>
                <td>' . e($course->end_date) . '</td>
                <td>' . $statusToggle . '</td>
                <td>
                    <a href="' . $showUrl . '" class="btn btn-sm btn-link">
                        <i class="la la-eye"></i> View Metrics
                    </a>
                    <button
                        type="button"
                        class="btn btn-sm btn-link"
                        onclick="openEditCourseModal(this)"
                        data-update-url="' . e($updateUrl) . '"
                        data-course-id="' . e($course->id) . '"
                        data-batch-id="' . e($batch->id) . '"
                        data-branch-id="' . e($branchId) . '"
                        data-centre-id="' . e($course->centre_id) . '"
                        data-centre-title="' . e($course->centre?->title ?? '') . '"
                        data-programme-id="' . e($course->programme_id) . '"
                        data-duration="' . e($course->duration ?? '') . '"
                        data-start-date="' . e($course->start_date ?? '') . '"
                        data-end-date="' . e($course->end_date ?? '') . '"
                        data-status="' . e($course->status ? 1 : 0) . '"
                    >
                        <i class="la la-edit"></i> Edit
                    </button>
                    ' . ($admittedCount > 0
                        ? '<button type="button" class="btn btn-sm btn-link text-muted" disabled title="Cannot delete: ' . e((string) $admittedCount) . ' admitted student(s) already assigned.">
                            <i class="la la-lock"></i> Delete
                        </button>'
                        : '<button
                            type="button"
                            class="btn btn-sm btn-link text-danger"
                            onclick="confirmDeleteBatchCourse(this)"
                            data-delete-url="' . e($deleteUrl) . '"
                            data-course-name="' . e($course->course_name ?? 'this course') . '"
                        >
                            <i class="la la-trash"></i> Delete
                        </button>'
                    ) . '
                </td>
            </tr>';
        }

        $html .= '</tbody></table>';

        if (!$isEmpty) {
            $html .= '<p id="batchCoursesNoResultsMsg" class="text-muted text-center py-4" style="display:none;">No matching courses found.</p>';
        }
        
        $html .= '<p id="batchCoursesEmptyMsg" class="text-muted text-center py-4" style="display:' . ($isEmpty ? 'block' : 'none') . ';">No courses assigned to this batch yet.</p>';

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
        $duration = request()->input('duration');
        $startDate = request()->input('start_date');
        $endDate = request()->input('end_date');

        // Validate required fields
        if (!$branchId || empty($centreIds) || empty($programmeIds)) {
            return redirect()
                ->back()
                ->with('error', 'Please select branch, at least one centre, and at least one programme.');
        }

        $createdCount = 0;
        $programmesById = Programme::query()
            ->whereIn('id', $programmeIds)
            ->get(['id', 'start_date', 'end_date'])
            ->keyBy('id');

        // Create a course for each combination of programme and centre
        foreach ($programmeIds as $programmeId) {
            $programme = $programmesById->get($programmeId);
            $programmeStartDate = $programme?->start_date;
            $programmeEndDate = $programme?->end_date;

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
                        'duration' => $duration,
                        'start_date' => $startDate ?: ($programmeStartDate ?: $batch->start_date),
                        'end_date' => $endDate ?: ($programmeEndDate ?: $batch->end_date),
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

    /**
     * Update an existing course (from the batch edit page modal).
     */
    public function updateCourse($courseId)
    {
        // Check permissions
        if (!backpack_user()->can('batch.update.all')) {
            abort(403, 'Unauthorized action.');
        }

        $data = request()->validate([
            'batch_id' => 'required|integer|exists:admission_batches,id',
            'centre_id' => 'required|integer|exists:centres,id',
            'programme_id' => 'required|integer|exists:programmes,id',
            'duration' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|boolean',
        ]);

        $batch = Batch::findOrFail($data['batch_id']);

        $course = Course::where('id', $courseId)
            ->where('batch_id', $batch->id)
            ->firstOrFail();

        // Prevent duplicates in the same batch
        $duplicateExists = Course::where('batch_id', $batch->id)
            ->where('centre_id', $data['centre_id'])
            ->where('programme_id', $data['programme_id'])
            ->where('id', '!=', $course->id)
            ->exists();

        if ($duplicateExists) {
            return redirect()
                ->back()
                ->with('error', 'A course with the selected centre and programme already exists in this batch.');
        }

        $course->centre_id = $data['centre_id'];
        $course->programme_id = $data['programme_id'];
        $course->duration = $data['duration'] ?? $course->duration;
        $course->start_date = $data['start_date'] ?: $course->start_date;
        $course->end_date = $data['end_date'] ?: $course->end_date;

        if (array_key_exists('status', $data)) {
            $course->status = (bool) $data['status'];
        }

        $course->save();

        return redirect()
            ->back()
            ->with('success', 'Course updated successfully.');
    }



}
