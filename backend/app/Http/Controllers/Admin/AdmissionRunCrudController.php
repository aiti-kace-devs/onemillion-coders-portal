<?php

namespace App\Http\Controllers\Admin;

use App\Models\Course;
use App\Models\Batch;
use App\Models\AdmissionRun;
use App\Services\AdmissionService;
use App\Services\AdmissionStatisticsService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;

class AdmissionRunCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\AdmissionRun::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/admission-run');
        CRUD::setEntityNameStrings('admission run', 'admission history');
    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'entity_name',
            'label' => 'Course / Programme',
            'type' => 'closure',
            'function' => function ($entry) {
                if ($entry->programme_id) {
                    return '[Programme] ' . ($entry->programme->title ?? 'N/A');
                }
                return '[Course] ' . ($entry->course->course_name ?? 'N/A');
            }
        ]);

        CRUD::addColumn([
            'name' => 'batch_title',
            'label' => 'Batch',
            'type' => 'closure',
            'function' => function ($entry) {
                return $entry->batch->title ?? 'N/A';
            }
        ]);

        CRUD::column('run_at')->type('datetime')->label('Run Date/Time');

        CRUD::addColumn([
            'name' => 'run_by_name',
            'label' => 'Run By',
            'type' => 'closure',
            'function' => function ($entry) {
                return $entry->admin->name ?? 'System';
            }
        ]);

        CRUD::column('selected_count')->type('number')->label('Selected');
        CRUD::column('admitted_count')->type('number')->label('Admitted');

        CRUD::addColumn([
            'name' => 'source_breakdown',
            'label' => 'Source (Auto/Manual)',
            'type' => 'closure',
            'function' => function ($entry) {
                return "{$entry->automated_count}/{$entry->manual_count}";
            }
        ]);

        CRUD::column('emailed_count')->type('number')->label('Emails');
        CRUD::column('accepted_count')->type('number')->label('Accepted');
        CRUD::column('rejected_count')->type('number')->label('Rejected');

        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'closure',
            'function' => function ($entry) {
                $badges = [
                    'completed' => '<span class="badge bg-success">Completed</span>',
                    'preview' => '<span class="badge bg-warning">Preview</span>',
                    'failed' => '<span class="badge bg-danger">Failed</span>',
                ];
                return $badges[$entry->status] ?? $entry->status;
            },
            'escaped' => false
        ]);

        CRUD::enableExportButtons();
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();

        CRUD::addColumn([
            'name' => 'rules_applied',
            'label' => 'Rules Applied',
            'type' => 'closure',
            'function' => function ($entry) {
                $rules = $entry->rules_applied ?? [];
                $html = '<ul>';
                foreach ($rules as $rule) {
                    $html .= "<li><strong>{$rule['name']}</strong> (Priority: {$rule['priority']})</li>";
                }
                $html .= '</ul>';
                return $html;
            },
            'escaped' => false
        ]);
    }

    /**
     * Show the admission run page
     */
    public function runAdmission()
    {
        $courses = Course::with('programme')->get();
        $programmes = \App\Models\Programme::orderBy('title')->get();
        $batches = Batch::where('status', true)->get();

        return view('admin.admission.run', compact('courses', 'programmes', 'batches'));
    }

    /**
     * Preview admission (AJAX)
     */
    public function previewAdmission(Request $request)
    {
        $validated = $request->validate([
            'course_id'      => 'nullable|exists:courses,id',
            'programme_id'   => 'nullable|exists:programmes,id',
            'limit'          => 'required|integer|min:1',
            'active_rules'   => 'nullable|array',
            'active_rules.*' => 'integer|exists:rules,id',
        ]);

        if (empty($validated['course_id']) && empty($validated['programme_id'])) {
            return response()->json(['success' => false, 'message' => 'Please select a course or programme'], 422);
        }

        $entity = !empty($validated['programme_id'])
            ? \App\Models\Programme::findOrFail($validated['programme_id'])
            : Course::findOrFail($validated['course_id']);

        $batchId = $entity instanceof Course
            ? $entity->batch->id
            : \App\Models\Batch::where('status', true)->latest()->first()?->id;

        $admissionService = app(AdmissionService::class);
        $preview = $admissionService->previewAdmission(
            $entity,
            $validated['limit'],
            $batchId,
            $validated['active_rules'] ?? null
        );

        // Format students for datatable
        $students = $preview['students']->map(function ($student) {
            return [
                'name' => $student->name,
                'email' => $student->email,
                'gender' => $student->gender,
                'age' => $student->age,
                'exam_score' => $student->examResults->first()?->yes_ans ?? 'N/A',
                'educational_level' => $student->educational_level ?? 'N/A',
                'applied_date' => $student->created_at->format('Y-m-d'),
            ];
        });

        return response()->json([
            'success' => true,
            'students' => $students,
            'stats' => $preview['stats'],
            'rules' => $preview['rules_applied']->map(fn($r) => [
                'name' => $r->name,
                'priority' => $r->pivot->priority
            ])->values()->toArray(),
        ]);
    }

    /**
     * Execute admission (AJAX)
     */
    public function executeAdmission(Request $request)
    {
        $validated = $request->validate([
            'course_id'      => 'nullable|exists:courses,id',
            'programme_id'   => 'nullable|exists:programmes,id',
            'limit'          => 'required|integer|min:1',
            'admit_all'      => 'nullable|boolean',
            'session_id'     => 'nullable|exists:course_sessions,id',
            'active_rules'   => 'nullable|array',
            'active_rules.*' => 'integer|exists:rules,id',
        ]);

        if (empty($validated['course_id']) && empty($validated['programme_id'])) {
            return response()->json(['success' => false, 'message' => 'Please select a course or programme'], 422);
        }

        $entity = !empty($validated['programme_id'])
            ? \App\Models\Programme::findOrFail($validated['programme_id'])
            : Course::findOrFail($validated['course_id']);

        $batchId = $entity instanceof Course
            ? $entity->batch->id
            : \App\Models\Batch::where('status', true)->latest()->first()?->id;

        $admin = backpack_user();

        try {
            $admissionService = app(AdmissionService::class);

            $admissionRun = $admissionService->executeAdmission(
                $entity,
                $validated['limit'],
                $batchId,
                $validated['session_id'] ?? null,
                $admin,
                $validated['active_rules'] ?? null,
                (bool) ($validated['admit_all'] ?? false)
            );

            return response()->json([
                'success' => true,
                'message' => "Successfully admitted {$admissionRun->admitted_count} students. Emails sent to {$admissionRun->emailed_count} students.",
                'admission_run_id' => $admissionRun->id,
                'admitted_count' => $admissionRun->admitted_count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Get rules for a course (AJAX)
     */
    public function getRules(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'nullable|exists:courses,id',
            'programme_id' => 'nullable|exists:programmes,id',
        ]);

        if (empty($validated['course_id']) && empty($validated['programme_id'])) {
            return response()->json(['success' => false, 'message' => 'Please select a course or programme'], 422);
        }

        $entity = !empty($validated['programme_id'])
            ? \App\Models\Programme::findOrFail($validated['programme_id'])
            : Course::findOrFail($validated['course_id']);

        $rules = $entity->getAllRules();

        return response()->json([
            'success' => true,
            'rules' => $rules->map(fn($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'is_active' => $r->is_active,
                'priority' => $r->pivot->priority,
                'params' => json_decode($r->pivot->value, true),
            ]),
        ]);
    }
}
