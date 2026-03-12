<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Programme;
use App\Models\User;
use App\Models\AdmissionRun;
use App\Models\Admin;
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
     * @param Course|Programme $entity
     * @param int $limit
     * @param int|null $batchId
     * @return array
     */
    /** Maximum students shown in preview for performance */
    const PREVIEW_DISPLAY_CAP = 200;

    public function previewAdmission(Course|Programme $entity, int $limit, ?int $batchId = null, ?array $activeRulesId = null): array
    {
        // Get effective rules
        $rules = $entity->getEffectiveRules();

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
        $query = $this->getBaseQuery($entity);

        // Apply pipeline
        $query = $this->applyPipeline($query, $rules);

        // Count all eligible students (true total for stats)
        $total = $query->count();

        // Cap preview display at 200 for performance regardless of limit
        $displayLimit = min($limit, self::PREVIEW_DISPLAY_CAP);
        $students = $query->with(['assessment'])
            ->limit($displayLimit)
            ->get();

        // Calculate statistics — total_selected reflects actual limit, not the display cap
        $stats = $this->calculateStatistics($students, $total);
        $stats['will_admit'] = min($limit, $total); // how many will actually be admitted
        $stats['preview_capped'] = $limit > self::PREVIEW_DISPLAY_CAP; // flag for UI
        $level = $entity instanceof Programme ? $entity->level : $entity->programme->level;

        return [
            'students' => $students,
            'stats' => $stats,
            'rules_applied' =>  $rules,
            'course_programme_level' => $level,
        ];
    }

    /**
     * Execute admission and create records
     *
     * @param Course|Programme $entity
     * @param int $limit
     * @param int $batchId
     * @param int|null $sessionId
     * @param Admin $admin
     * @return AdmissionRun
     */
    public function executeAdmission(
        Course|Programme $entity,
        int $limit,
        int $batchId,
        ?int $sessionId = null,
        ?Admin $admin = null,
        ?array $activeRulesId = null,
        bool $admitAll = false
    ): AdmissionRun {
        DB::beginTransaction();

        try {
            // Build rules (mirrors previewAdmission logic)
            $rules = $entity->getEffectiveRules();
            if ($activeRulesId !== null) {
                $rules = $rules->filter(fn($r) => in_array($r->id, $activeRulesId));
            }
            if ($activeRulesId == null && $rules->count() > 0) {
                $rules = collect([]);
            }

            // Build the filtered query directly — do NOT load all students into memory
            $query = $this->getBaseQuery($entity);
            $query = $this->applyPipeline($query, $rules);

            $totalEligible = $query->count();
            $selectedCount = $admitAll ? $totalEligible : min($limit, $totalEligible);

            // Create admission run record
            $admissionRun = AdmissionRun::create([
                'course_id'     => $entity instanceof Course ? $entity->id : null,
                'programme_id'  => $entity instanceof Programme ? $entity->id : null,
                'batch_id'      => $batchId,
                'run_by'        => $admin?->id ?? backpack_user()?->id,
                'run_at'        => now(),
                'rules_applied' => $rules->map(fn($r) => [
                    'id'       => $r->id,
                    'name'     => $r->name,
                    'priority' => $r->pivot->priority,
                    'value'    => $r->pivot->value,
                ])->toArray(),
                'selected_count'  => $selectedCount,
                'admitted_count'  => 0,
                'emailed_count'   => 0,
                'automated_count' => 0,
                'status'          => 'preview',
            ]);

            // Dispatch jobs in chunks to avoid memory exhaustion
            $admittedCount = 0;
            $query->chunkById(200, function ($students) use (
                $entity,
                $sessionId,
                $admissionRun,
                &$admittedCount,
                $admitAll,
                $limit
            ) {
                foreach ($students as $student) {
                    if (!$admitAll && $admittedCount >= $limit) {
                        return false; // stop chunking once limit is reached
                    }
                    try {
                        $studentCourseId = $entity instanceof Course ? $entity->id : $student->registered_course;
                        CreateStudentAdmissionJob::dispatch($student->id, $studentCourseId, $sessionId, 'automated', $admissionRun->id);
                        $admittedCount++;
                    } catch (\Exception $e) {
                        Log::error("Failed to dispatch admission job", [
                            'user_id' => $student->userId ?? $student->id,
                            'error'   => $e->getMessage()
                        ]);
                    }
                }
            });

            // Update admission run
            $admissionRun->update([
                'admitted_count'  => $admittedCount,
                'automated_count' => $admittedCount,
                'status'          => 'completed',
            ]);

            // Invalidate cache
            if ($entity instanceof Course) {
                app(AdmissionStatisticsService::class)->invalidateCache($entity, $entity->batch ?? \App\Models\Batch::find($batchId));
            }

            DB::commit();

            Log::info("Admission executed successfully", [
                'run_id' => $admissionRun->id,
                'entity_type' => get_class($entity),
                'entity_id' => $entity->id,
                'admitted_count' => $admittedCount,
            ]);

            return $admissionRun;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Admission execution failed", [
                'entity_type' => get_class($entity),
                'entity_id' => $entity->id,
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
     * @param Course|Programme $entity
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getBaseQuery(Course|Programme $entity)
    {
        // based query uses student_level as this is only set after assessment
        $query = User::query()
            // ->whereHas('assessment')  TODO: activate after assessment is implemented
            ->whereNotNull('student_level') // TODO: remove after assessment is implemented
            ->whereDoesntHave('admission'); // Exclude already admitted

        if ($entity instanceof Course) {
            $query->where('registered_course', $entity->id)
                ->whereDoesntHave('rejectedAdmissions', function ($q) use ($entity) {
                    $q->where('course_id', $entity->id);
                });
        } elseif ($entity instanceof Programme) {
            // Get all course IDs for the programme
            $courseIds = $entity->courses()->pluck('id');
            $query->whereIn('registered_course', $courseIds)
                ->whereDoesntHave('rejectedAdmissions', function ($q) use ($courseIds) {
                    $q->whereIn('course_id', $courseIds);
                });
        }

        return $query;
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

        // $examScores = $students->map(function ($student) {
        //     return $student->examResults->first()?->yes_ans ?? 0;
        // })->filter();

        // calculate level distribution
        $levelDistribution = [
            User::beginner => $students->where('student_level', User::beginner)->count(),
            User::intermediate => $students->where('student_level', User::intermediate)->count(),
            User::advanced => $students->where('student_level', User::advanced)->count(),
        ];

        return [
            'total_selected' => $students->count(),
            'gender_breakdown' => $genderBreakdown,
            'avg_exam_score' => 0,
            'date_range' => [
                'oldest' => $students->min('created_at'),
                'newest' => $students->max('created_at'),
            ],
            'level_distribution' => $levelDistribution,
            'total' => $total ?? $students->count(),
        ];
    }
}
