<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Centre;
use App\Models\Course;
use App\Models\Batch;
use App\Models\Admin;
use App\Services\AdmissionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoAdmitStudentsCommand extends Command
{
    protected $signature = 'app:auto-admit 
                            {course? : Course ID}
                            {--dry-run : Preview without executing}
                            {--limit= : Number of students to admit}
                            {--batch-id= : Specific batch ID}';

    protected $description = 'Run automated admission for courses using configured pipeline rules';

    public function handle()
    {
        $courseId = $this->argument('course');

        // Interactive course selection if not provided
        if (!$courseId) {
            $this->line("\n<fg=cyan>╔════════════════════════════════════════╗</>");
            $this->line("<fg=cyan>║   🎓 Automated Admission System       ║</>");
            $this->line("<fg=cyan>╚════════════════════════════════════════╝</>\n");

            $courseId = $this->selectCourseInteractively();

            if (!$courseId) {
                $this->error('No course selected. Aborting.');
                return 1;
            }
        }

        // Load course with relationships
        $course = Course::with(['programme', 'batch', 'batches'])->find($courseId);

        if (!$course) {
            $this->error("Course with ID {$courseId} not found.");
            return 1;
        }

        $this->newLine();
        $this->line("📚 <fg=green>Course:</> {$course->course_name}");
        $programmeName = $course->programme->title ?? 'N/A';
        $this->line("📖 <fg=green>Programme:</> {$programmeName}");

        // Get batch (use active batch as default)
        $batchId = $this->option('batch-id');
        $batch = $batchId
            ? Batch::find($batchId)
            : ($course->batch ?? $course->batches()->where('status', true)->latest()->first());

        if (!$batch) {
            $this->error("No active batch found for this course.");
            return 1;
        }

        $this->line("📦 <fg=green>Batch:</> {$batch->title}");

        // Get limit
        $limit = $this->option('limit')
            ?? $course->auto_admit_limit
            ?? $this->ask('How many students to admit?', 50);

        $this->newLine();

        try {
            $admissionService = app(AdmissionService::class);

            // Preview mode
            if ($this->option('dry-run')) {
                return $this->runPreview($admissionService, $course, $batch, (int)$limit);
            }

            // Confirm execution
            if (!$this->confirm("Proceed to admit {$limit} students?", true)) {
                $this->warn('Operation cancelled.');
                return 0;
            }

            // Execute admission
            return $this->runAdmission($admissionService, $course, $batch, (int)$limit);
        } catch (\Exception $e) {
            $this->newLine();
            $this->error("❌ Error: {$e->getMessage()}");

            Log::error("Admission failed via command", [
                'course_id' => $course->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }
    }

    protected function runPreview($admissionService, $course, $batch, $limit)
    {
        $this->warn('🔍 DRY RUN MODE - No changes will be made');
        $this->newLine();

        $preview = $admissionService->previewAdmission($course, $limit, $batch->id);

        $totalSelected = $preview['stats']['total_selected'] ?? 0;
        $genderBreakdown = $preview['stats']['gender_breakdown'] ?? ['male' => 0, 'female' => 0];
        $avgScore = $preview['stats']['avg_exam_score'] ?? 0;

        if ($totalSelected == 0) {
            $this->error('❌ No eligible students found matching the pipeline rules.');
            return 0;
        }

        $this->info("✅ Would admit {$totalSelected} student(s)");
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Gender (Male/Female)', "{$genderBreakdown['male']}/{$genderBreakdown['female']}"],
                ['Average Exam Score', number_format($avgScore, 2)],
                ['Batch', $batch->title],
                ['Source', 'Automated'],
            ]
        );

        // Show sample students
        if ($preview['students']->isNotEmpty()) {
            $this->newLine();
            $this->line('<fg=cyan>Sample Students (first 10):</>');

            $sampleData = $preview['students']->take(10)->map(function ($student) {
                $examScore = $student->examResults->first()->yes_ans ?? 'N/A';
                return [
                    $student->name,
                    $student->email,
                    $student->gender,
                    $examScore,
                    $student->created_at->format('Y-m-d'),
                ];
            })->toArray();

            $this->table(
                ['Name', 'Email', 'Gender', 'Score', 'Applied'],
                $sampleData
            );
        }

        $this->newLine();
        $this->info('💡 Run without --dry-run to execute admission');
        return 0;
    }

    protected function runAdmission($admissionService, $course, $batch, $limit)
    {
        $this->newLine();
        $this->line('⏳ Running admission pipeline...');

        $admin = Admin::first(); // System admin

        $admissionRun = $admissionService->executeAdmission(
            $course,
            $limit,
            $batch->id,
            null, // session_id
            $admin
        );

        // Update course if auto-admission is enabled
        if ($course->auto_admit_enabled) {
            $course->update(['last_auto_admit_at' => now()]);
        }

        $this->newLine();
        $this->info("✅ Successfully admitted {$admissionRun->admitted_count} students");
        $this->line("   📧 Emails queued: {$admissionRun->emailed_count}");
        $this->line("   📊 Run ID: {$admissionRun->id}");

        Log::info("Admission completed via command", [
            'course_id' => $course->id,
            'batch_id' => $batch->id,
            'admitted_count' => $admissionRun->admitted_count,
            'run_id' => $admissionRun->id,
            'trigger' => 'console'
        ]);

        $this->newLine();
        $this->line('🎉 <fg=green>Admission completed successfully!</>');

        return 0;
    }

    protected function selectCourseInteractively(): ?int
    {
        // Step 1: Select Branch
        $branches = Branch::all();

        if ($branches->isEmpty()) {
            $this->error('No branches found in the system.');
            return null;
        }

        $branchOptions = $branches->map(fn($b) => [
            'id' => $b->id,
            'data' => $b->title
        ])->all();

        $selectedBranch = $this->askForOptionSelection($branchOptions, 'Branch');

        if (!$selectedBranch) {
            return null;
        }

        // Step 2: Select Centre
        $centres = Centre::where('branch_id', $selectedBranch)->get();

        if ($centres->isEmpty()) {
            $this->error("No centres found for selected branch.");
            return null;
        }

        $centreOptions = $centres->map(fn($c) => [
            'id' => $c->id,
            'data' => $c->title
        ])->all();

        $selectedCentre = $this->askForOptionSelection($centreOptions, 'Centre');

        if (!$selectedCentre) {
            return null;
        }

        // Step 3: Select Course
        $courses = Course::where('centre_id', $selectedCentre)
            ->with('programme')
            ->get();

        if ($courses->isEmpty()) {
            $this->error("No courses found for selected centre.");
            return null;
        }

        $courseOptions = $courses->map(fn($c) => [
            'id' => $c->id,
            'data' => $c->course_name . " (Programme: " . ($c->programme->title ?? 'N/A') . ")"
        ])->all();

        return $this->askForOptionSelection($courseOptions, 'Course');
    }

    protected function askForOptionSelection(array $options, string $message): ?int
    {
        $this->line("*****************************************************");
        $this->line("<fg=yellow>Select One: [Number] {$message}</>");
        $this->newLine();

        foreach ($options as $option) {
            $this->comment("  [{$option['id']}] {$option['data']}");
        }

        $this->line("*****************************************************");

        $selection = $this->ask("Type number");

        // Validate selection
        $validIds = array_column($options, 'id');

        if (!in_array($selection, $validIds)) {
            $this->error("Invalid selection: {$selection}");
            return null;
        }

        return (int)$selection;
    }
}
