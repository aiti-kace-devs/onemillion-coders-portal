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
            'name' => 'course_name',
            'label' => 'Course',
            'type' => 'closure',
            'function' => function($entry) {
                return $entry->course->course_name ?? 'N/A';
            }
        ]);

        CRUD::addColumn([
            'name' => 'batch_title',
            'label' => 'Batch',
            'type' => 'closure',
            'function' => function($entry) {
                return $entry->batch->title ?? 'N/A';
            }
        ]);

        CRUD::column('run_at')->type('datetime')->label('Run Date/Time');

        CRUD::addColumn([
            'name' => 'run_by_name',
            'label' => 'Run By',
            'type' => 'closure',
            'function' => function($entry) {
                return $entry->admin->name ?? 'System';
            }
        ]);

        CRUD::column('selected_count')->type('number')->label('Selected');
        CRUD::column('admitted_count')->type('number')->label('Admitted');
        
        CRUD::addColumn([
            'name' => 'source_breakdown',
            'label' => 'Source (Auto/Manual)',
            'type' => 'closure',
            'function' => function($entry) {
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
            'function' => function($entry) {
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
            'function' => function($entry) {
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
        $batches = Batch::where('status', true)->get();
        
        return view('admin.admission.run', compact('courses', 'batches'));
    }

    /**
     * Preview admission (AJAX)
     */
    public function previewAdmission(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'batch_id' => 'required|exists:admission_batches,id',
            'limit' => 'required|integer|min:1|max:200',
        ]);

        $course = Course::findOrFail($validated['course_id']);
        $batch = Batch::findOrFail($validated['batch_id']);
        
        $admissionService = app(AdmissionService::class);
        $preview = $admissionService->previewAdmission($course, $validated['limit'], $batch->id);

        // Format students for datatable
        $students = $preview['students']->map(function($student) {
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
            ]),
        ]);
    }

    /**
     * Execute admission (AJAX)
     */
    public function executeAdmission(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'batch_id' => 'required|exists:admission_batches,id',
            'limit' => 'required|integer|min:1|max:200',
            'session_id' => 'nullable|exists:course_sessions,id',
        ]);

        $course = Course::findOrFail($validated['course_id']);
        $batch = Batch::findOrFail($validated['batch_id']);
        $admin = backpack_user();

        try {
            $admissionService = app(AdmissionService::class);
            
            $admissionRun = $admissionService->executeAdmission(
                $course,
                $validated['limit'],
                $batch->id,
                $validated['session_id'] ?? null,
                $admin
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
}
