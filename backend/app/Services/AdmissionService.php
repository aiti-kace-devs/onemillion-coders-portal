<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use App\Models\UserAdmission;
use App\Models\AdmissionRun;
use App\Models\Admin;
use App\Jobs\AdmitStudentJob;
use App\Jobs\CreateStudentAdmissionJob;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pipeline\Pipeline;

class AdmissionService
{
    /**
     * Preview admission without creating records
     *
     * @param Course $course
     * @param int $limit
     * @param int|null $batchId
     * @return array
     */
    public function previewAdmission(Course $course, int $limit, ?int $batchId = null, ?array $activeRulesId = null): array
    {
        // Get effective rules
        $rules = $course->getEffectiveRules();

        // Filter rules if activeRulesId is provided
        if ($activeRulesId !== null) {
            $rules = $rules->filter(function ($rule) use ($activeRulesId) {
                return in_array($rule->id, $activeRulesId);
            });
        }

        if ($activeRulesId == null && $rules->count() > 0) {
            $rules = collect([]);
        }

        // Build base query
        $query = $this->getBaseQuery($course);

        // Apply pipeline
        $query = $this->applyPipeline($query, $rules);

        // Get students with exam results
        $students = $query->with(['examResults', 'formResponse'])
            ->limit($limit)
            ->get();

        $total = $query->count();

        // Calculate statistics
        $stats = $this->calculateStatistics($students, $total);

        return [
            'students' => $students,
            'stats' => $stats,
            'rules_applied' =>  $rules,
        ];
    }

    /**
     * Execute admission and create records
     *
     * @param Course $course
     * @param int $limit
     * @param int $batchId
     * @param int|null $sessionId
     * @param Admin $admin
     * @return AdmissionRun
     */
    public function executeAdmission(
        Course $course,
        int $limit,
        int $batchId,
        ?int $sessionId = null,
        ?Admin $admin = null,
        ?array $activeRulesId = null
    ): AdmissionRun {
        DB::beginTransaction();

        try {
            // Get preview data
            $preview = $this->previewAdmission($course, $limit, $batchId, $activeRulesId);
            $students = $preview['students'];
            $rules = $preview['rules_applied'];

            // Create admission run record
            $admissionRun = AdmissionRun::create([
                'course_id' => $course->id,
                'batch_id' => $batchId,
                'run_by' => $admin?->id ?? backpack_user()?->id,
                'run_at' => now(),
                'rules_applied' => $rules->map(function ($rule) {
                    return [
                        'id' => $rule->id,
                        'name' => $rule->name,
                        'priority' => $rule->pivot->priority,
                        'value' => $rule->pivot->value,
                    ];
                })->toArray(),
                'selected_count' => $students->count(),
                'admitted_count' => 0,
                'emailed_count' => 0,
                'automated_count' => 0,
                'status' => 'preview',
            ]);

            // Create admission records
            $admittedCount = 0;
            $emailedCount = 0;

            foreach ($students as $student) {
                try {
                    CreateStudentAdmissionJob::dispatch($student->id, $course->id, $sessionId, 'automated', $admissionRun->id);
                    $admittedCount++;
                } catch (\Exception $e) {
                    Log::error("Failed to dispatch admission email", [
                        'user_id' => $student->userId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Update admission run
            $admissionRun->update([
                'admitted_count' => $admittedCount,
                'emailed_count' => $emailedCount,
                'automated_count' => $admittedCount,
                'status' => 'completed',
            ]);

            // Invalidate cache
            app(AdmissionStatisticsService::class)->invalidateCache($course, $course->batch);

            DB::commit();

            Log::info("Admission executed successfully", [
                'run_id' => $admissionRun->id,
                'course_id' => $course->id,
                'admitted_count' => $admittedCount,
            ]);

            return $admissionRun;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Admission execution failed", [
                'course_id' => $course->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Legacy method for API compatibility
     */
    public function admitNextBatch(
        Course $course,
        int $limit,
        ?int $offset = null,
        ?int $batchId = null,
        ?int $sessionId = null
    ): Collection {
        $admin = Admin::first(); // System admin
        $batchId = $batchId ?? $course->batch_id ?? $course->batches()->latest()->first()?->id;

        if (!$batchId) {
            throw new \Exception("No batch found for course");
        }

        $admissionRun = $this->executeAdmission($course, $limit, $batchId, $sessionId, $admin);

        return $admissionRun->course->admissions()->where('batch_id', $batchId)->latest()->limit($limit)->get();
    }

    /**
     * Get base query with exclusions
     *
     * @param Course $course
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getBaseQuery(Course $course)
    {
        return User::query()
            ->where('registered_course', $course->id)
            ->whereHas('examResults', function ($q) use ($course) {
                // get the pass percentage

                $q->whereRaw('ROUND ((yes_ans / (no_ans + yes_ans)) * 100, 2) >= CAST(? AS DECIMAL)', config(MINIMUM_EXAM_PASS_PERCENTAGE, 30));
            })
            ->whereDoesntHave('admission') // Exclude already admitted
            ->whereDoesntHave('rejectedAdmissions', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            });
    }

    /**
     * Apply pipeline of rules to query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Collection $rules
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyPipeline($query, Collection $rules)
    {
        $pipeline = $rules->map(function ($rule) {
            return function ($query, $next) use ($rule) {
                $ruleInstance = $rule->instantiate($rule->pivot->value);
                return $ruleInstance->apply($query, $rule->pivot->value, $next);
            };
        })->toArray();

        return app(Pipeline::class)
            ->send($query)
            ->through($pipeline)
            ->thenReturn();
    }

    /**
     * Calculate statistics from student collection
     *
     * @param Collection $students
     * @return array
     */
    protected function calculateStatistics(Collection $students, ?int $total): array
    {
        $genderBreakdown = [
            'male' => $students->where('gender', 'male')->count(),
            'female' => $students->where('gender', 'female')->count(),
        ];

        $examScores = $students->map(function ($student) {
            return $student->examResults->first()?->yes_ans ?? 0;
        })->filter();

        return [
            'total_selected' => $students->count(),
            'gender_breakdown' => $genderBreakdown,
            'avg_exam_score' => $examScores->isNotEmpty() ? round($examScores->avg(), 2) : 0,
            'date_range' => [
                'oldest' => $students->min('created_at'),
                'newest' => $students->max('created_at'),
            ],
            'total' => $total ?? $students->count(),
        ];
    }
}
