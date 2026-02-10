<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Batch;
use App\Models\UserAdmission;
use Illuminate\Support\Facades\Cache;

class AdmissionStatisticsService
{
    protected int $cacheTtl = 86400; // 24 hours in seconds

    /**
     * Get admission statistics (from cache or database)
     *
     * @param Course $course
     * @param Batch $batch
     * @param bool $refresh Force refresh cache
     * @return array
     */
    public function getStatistics(Course $course, Batch $batch, bool $refresh = false): array
    {
        $cacheKey = $this->getCacheKey($course->id, $batch->id);

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($course, $batch) {
            return $this->calculateStatistics($course, $batch);
        });
    }

    /**
     * Calculate statistics from database
     *
     * @param Course $course
     * @param Batch $batch
     * @return array
     */
    protected function calculateStatistics(Course $course, Batch $batch): array
    {
        $admissions = UserAdmission::where('course_id', $course->id)
            ->where('batch_id', $batch->id)
            ->with('user.examResults')
            ->get();

        $totalAdmitted = $admissions->count();
        $manualAdmissions = $admissions->where('admission_source', 'manual')->count();
        $automatedAdmissions = $admissions->where('admission_source', 'automated')->count();
        $emailsSent = $admissions->where('email_sent', true)->count();
        $accepted = $admissions->whereNotNull('confirmed')->count();
        $rejected = $totalAdmitted - $accepted;
        $pending = $admissions->whereNull('confirmed')->where('email_sent', true)->count();

        // Gender breakdown
        $maleCount = 0;
        $femaleCount = 0;

        foreach ($admissions as $admission) {
            if ($admission->user) {
                if ($admission->user->gender === 'male') {
                    $maleCount++;
                } elseif ($admission->user->gender === 'female') {
                    $femaleCount++;
                }
            }
        }

        // Average exam score
        $examScores = [];
        foreach ($admissions as $admission) {
            if ($admission->user && $admission->user->examResults->isNotEmpty()) {
                $score = $admission->user->examResults->first()->yes_ans ?? null;
                if ($score !== null) {
                    $examScores[] = $score;
                }
            }
        }

        $avgExamScore = count($examScores) > 0 ? round(array_sum($examScores) / count($examScores), 2) : 0;

        return [
            'total_admitted' => $totalAdmitted,
            'manual_admissions' => $manualAdmissions,
            'automated_admissions' => $automatedAdmissions,
            'emails_sent' => $emailsSent,
            'accepted' => $accepted,
            'rejected' => $rejected,
            'pending' => $pending,
            'gender_breakdown' => [
                'male' => $maleCount,
                'female' => $femaleCount,
            ],
            'avg_exam_score' => $avgExamScore,
            'last_updated' => now()->toDateTimeString(),
        ];
    }

    /**
     * Invalidate cache for specific course/batch
     *
     * @param Course $course
     * @param Batch|null $batch
     * @return void
     */
    public function invalidateCache(Course $course, ?Batch $batch = null): void
    {
        if ($batch) {
            Cache::forget($this->getCacheKey($course->id, $batch->id));
        }

        Cache::forget("admission_stats:course:{$course->id}");
        Cache::forget("admission_stats:global");
    }

    /**
     * Refresh cache for specific course/batch
     *
     * @param Course $course
     * @param Batch $batch
     * @return array
     */
    public function refreshCache(Course $course, Batch $batch): array
    {
        return $this->getStatistics($course, $batch, refresh: true);
    }

    /**
     * Get cache key
     *
     * @param int $courseId
     * @param int $batchId
     * @return string
     */
    protected function getCacheKey(int $courseId, int $batchId): string
    {
        return "admission_stats:{$courseId}:{$batchId}";
    }
}
