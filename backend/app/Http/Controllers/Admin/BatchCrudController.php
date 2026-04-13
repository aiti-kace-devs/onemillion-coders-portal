<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\BatchRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\CrudPanel\Hooks\Facades\LifecycleHook;
use App\Helpers\WidgetHelper;
use App\Helpers\FilterHelper;
use App\Models\Batch;
use App\Helpers\CourseFieldHelpers;
use App\Helpers\BatchFieldHelpers;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use App\Helpers\CrudListHelper;
use App\Services\ProgrammeBatchGenerator;

/**
 * Class BatchCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class BatchCrudController extends CrudController
{
    use CourseFieldHelpers;
    use BatchFieldHelpers;
    use \App\SearchableCRUD;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Batch::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/batch');
        CRUD::setEntityNameStrings('batch', 'batches');

        $this->setSearchableColumns(['name', 'description']);
        $this->setSearchResultAttributes(['id', 'name', 'description']);

        // Add permission checks
        LifecycleHook::hookInto(['list:before_setup', 'show:before_setup'], function () {
            $this->crud->addClause('where', function ($query) {
                if (!backpack_user()->can('batch.read.all')) {
                    // Add any specific filtering logic here if needed
                }
            });
        });
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        WidgetHelper::admissionBatchStatisticsWidget();
        CrudListHelper::editInDropdown();

        // Check permissions
        if (!backpack_user()->can('batch.read.all')) {
            abort(403, 'Unauthorized action.');
        }

        $this->setupCommonBatchListFields();

        // CRUD::column('total_completed_students')->label('Total Completed');
        // FilterHelper::addBooleanColumn('status', 'status');
        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
        ]);

        CRUD::addColumn([
            'name' => 'completed',
            'label' => 'Completed',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
            'toggle_url' => 'batch/{id}/toggle-completed',
        ]);

        FilterHelper::addBooleanColumn('completed', 'completed');
        // $this->courseFilter('course_id');
        $this->addOngoingCoursesFilter('Ongoing Batches');
        FilterHelper::addBooleanFilter('completed', 'Filter By Completed');
        FilterHelper::addDateRangeFilter('created_at', 'Created At');
        CRUD::enableExportButtons();
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        // Check permissions
        if (!backpack_user()->can('batch.create')) {
            abort(403, 'Unauthorized action.');
        }

        CRUD::setValidation(BatchRequest::class);

        $this->setupCommonBatchFields();

        CRUD::removeButton('save_and_new');
        CRUD::removeButton('save_and_preview');
        CRUD::removeButton('preview');

        $this->addCoursesManagementSection();
    }

    /**
     * Define what happens when the Update operation is loaded.
     */
    protected function setupUpdateOperation()
    {
        // Check permissions
        if (!backpack_user()->can('batch.update.all')) {
            abort(403, 'Unauthorized action.');
        }

        CRUD::setValidation(BatchRequest::class);

        // Hide extra save buttons - only show "Save and edit this item"
        CRUD::removeButton('save_and_new');
        CRUD::removeButton('save_and_preview');
        CRUD::removeButton('preview');

        $this->addCoursesManagementSection();

        $this->setupCommonBatchFields();
    }




    protected function setupShowOperation()
    {
        $this->setupCommonBatchListFields();

        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
        ]);

        CRUD::addColumn([
            'name' => 'completed',
            'label' => 'Completed',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
            'toggle_url' => 'batch/{id}/toggle-completed',
        ]);
    }




    /**
     * Add courses management section to the edit page
     */
    protected function addCoursesManagementSection()
    {
        $batch = $this->crud->getCurrentEntry();

        if (!$batch) {
            // On create, show a message that courses can be added after saving
            CRUD::addField([
                'name' => 'courses_notice',
                'type' => 'custom_html',
                'value' => '<div class="alert alert-info">
                    <i class="la la-info-circle"></i>
                    <strong>Assign Courses:</strong> Save this batch first, then you can assign courses on the edit page.
                </div>',
                'tab' => 'Assign Courses',
            ]);
            return;
        }

        // Regenerate programme batches button
        CRUD::addField([
            'name' => 'regenerate_batches',
            'type' => 'custom_html',
            'value' => '<div class="mb-3">
                <a href="' . url('admin/batch/' . $batch->id . '/regenerate-batches') . '"
                   class="btn btn-warning"
                   onclick="event.preventDefault(); if(confirm(\'Regenerate programme batches for this admission? This may overwrite existing batches.\')) { document.getElementById(\'regenerate-batches-form\').submit(); }">
                    <i class="la la-refresh"></i> Regenerate Programme Batches
                </a>
                <form id="regenerate-batches-form" action="' . url('admin/batch/' . $batch->id . '/regenerate-batches') . '" method="POST" style="display: none;">
                    ' . csrf_field() . '
                </form>
            </div>',
            'tab' => 'Assign Courses',
        ]);

        CRUD::addField([
            'name' => 'add_course_modal',
            'type' => 'custom_html',
            'value' => $this->getAddCourseModalHtml($batch),
            'tab' => 'Assign Courses',
        ]);

        CRUD::addField([
            'name' => 'course_actions',
            'type' => 'custom_html',
            'value' => $this->getCoursesActionsHtml($batch),
            'tab' => 'Assign Courses',
        ]);
    }




    /**
     * Define what happens when the Delete operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-delete
     * @return void
     */
    protected function setupDeleteOperation()
    {
        // Check permissions
        if (!backpack_user()->can('batch.delete.all')) {
            abort(403, 'Unauthorized action.');
        }
    }




    public function store()
    {
        // Check permissions
        if (!backpack_user()->can('batch.create')) {
            abort(403, 'Unauthorized action.');
        }

        return $this->traitStore();
    }

    public function update()
    {
        // Check permissions
        if (!backpack_user()->can('batch.update.self')) {
            abort(403, 'Unauthorized action.');
        }

        $response = $this->traitUpdate();

        return $response;
    }

    /**
     * Prevent deleting batches that have courses assigned.
     */
    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');

        if (!backpack_user()->can('batch.delete.all')) {
            abort(403, 'Unauthorized action.');
        }

        $id = $this->crud->getCurrentEntryId() ?: $id;

        // Use raw counts (no model scopes/events). If courses exist, we block deletion.
        $coursesCount = (int) DB::table('courses')
            ->where('batch_id', $id)
            ->count();

        if ($coursesCount > 0) {
            $count = $coursesCount;
            return response()->json([
                'error' => [sprintf(
                    "%d %s already assigned to this batch, so you can't delete it.",
                    $count,
                    \Illuminate\Support\Str::plural('course', $count)
                )],
            ]);
        }

        // If there are any legacy records in other tables, unlink them first.
        // According to our schema, these are nullable and should be null-on-delete; we enforce that here.
        DB::beginTransaction();

        try {
            DB::table('user_admission')->where('batch_id', $id)->update(['batch_id' => null]);

            $result = $this->crud->delete($id);
            DB::commit();

            return $result;
        } catch (QueryException $e) {
            DB::rollBack();

            // Handle FK constraint errors gracefully (e.g., hidden/legacy linked records).
            if ((string) $e->getCode() === '23000') {
                $message = $e->getMessage();

                // Try to parse the MySQL FK error to show a specific table/column to check.
                $fkDetails = null;
                if (preg_match('/foreign key constraint fails \\(`[^`]+`\\.`(?P<table>[^`]+)`, CONSTRAINT `(?P<constraint>[^`]+)` FOREIGN KEY \\(`(?P<column>[^`]+)`\\)/i', $message, $m)) {
                    $fkDetails = [
                        'table' => $m['table'] ?? null,
                        'constraint' => $m['constraint'] ?? null,
                        'column' => $m['column'] ?? null,
                    ];
                }

                if ($fkDetails && $fkDetails['table'] && $fkDetails['column']) {
                    try {
                        $count = (int) DB::table($fkDetails['table'])
                            ->where($fkDetails['column'], $id)
                            ->count();

                        if ($count > 0) {
                            // Special-case courses: we always block if courses exist.
                            if ($fkDetails['table'] === 'courses' && $fkDetails['column'] === 'batch_id') {
                                return response()->json([
                                    'error' => [sprintf(
                                        "%d %s already assigned to this batch, so you can't delete it.",
                                        $count,
                                        \Illuminate\Support\Str::plural('course', $count)
                                    )],
                                ]);
                            }

                            return response()->json([
                                'error' => [sprintf(
                                    "Cannot delete this batch: %d record(s) in %s still reference it (%s).",
                                    $count,
                                    $fkDetails['table'],
                                    $fkDetails['column']
                                )],
                            ]);
                        }

                        return response()->json([
                            'error' => [sprintf(
                                "Cannot delete this batch due to a foreign key constraint (%s on %s.%s).",
                                $fkDetails['constraint'] ?: 'unknown',
                                $fkDetails['table'],
                                $fkDetails['column']
                            )],
                        ]);
                    } catch (\Throwable $ignored) {
                        // If counting fails, fall back to a generic message.
                    }
                }

                $debugHint = config('app.debug') ? (' ' . $message) : '';

                return response()->json([
                    'error' => ['Unable to delete this batch because it is still referenced by other records.' . $debugHint],
                ]);
            }

            throw $e;
        }
    }

    /**
     * Toggle batch status from the List view.
     */
    public function toggleStatus(Request $request, $id)
    {
        if (!backpack_user()->can('batch.update.all')) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'value' => 'required|boolean',
        ]);

        $batch = Batch::findOrFail($id);
        $newValue = (bool) $data['value'];

        if ($newValue) {
            if ($batch->completed) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot activate batch: this batch is marked as completed.',
                ], 422);
            }

            if (!$batch->start_date || !$batch->end_date) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot activate batch: start and end dates are required.',
                ], 422);
            }

            $conflictingBatch = Batch::query()
                ->where('status', true)
                ->where('id', '!=', $batch->id)
                ->orderBy('start_date')
                ->first();

            if ($conflictingBatch) {
                $conflictRange = trim(($conflictingBatch->start_date ?? '') . ' to ' . ($conflictingBatch->end_date ?? ''));

                return response()->json([
                    'status' => 'error',
                    'message' => "Cannot activate batch: another batch is already active ({$conflictingBatch->title}) scheduled for {$conflictRange}.",
                ], 422);
            }
        }

        $batch->status = $newValue;
        $batch->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Batch status updated successfully.',
            'value' => $batch->status ? 1 : 0,
        ]);
    }

    /**
     * Toggle batch completion from the List view.
     */
    public function toggleCompleted(Request $request, $id)
    {
        if (!backpack_user()->can('batch.update.all')) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'value' => 'required|boolean',
        ]);

        $batch = Batch::findOrFail($id);
        $newValue = (bool) $data['value'];

        $batch->completed = $newValue;

        // A completed batch should never be active.
        if ($newValue) {
            $batch->status = false;
        }

        $batch->save();

        $updates = [
            'completed' => $batch->completed ? 1 : 0,
        ];

        if ($newValue) {
            $updates['status'] = 0;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Batch completion updated successfully.',
            'value' => $batch->completed ? 1 : 0,
            'updates' => $updates,
        ]);
    }

    /**
     * Regenerate programme batches for this admission batch.
     */
    public function regenerate(Request $request, int $id)
    {
        $batch = Batch::findOrFail($id);

        $generator = app(ProgrammeBatchGenerator::class);
        $generated = $generator->generate($batch);

        return redirect()->back()
            ->with('success', "Regenerated {$generated->count()} programme batches for '{$batch->title}'.");
    }
}
