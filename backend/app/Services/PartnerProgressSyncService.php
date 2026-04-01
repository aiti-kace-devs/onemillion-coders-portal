<?php

namespace App\Services;

use App\Jobs\RefreshPartnerProgressJob;
use App\Models\PartnerProgressSyncAudit;
use App\Models\StudentPartnerProgress;
use App\Models\StudentPartnerProgressHistory;
use App\Models\User;
use App\Services\Partners\Startocode\PartnerProgressClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PartnerProgressSyncService
{
    public function __construct(
        private readonly PartnerCourseEligibilityService $eligibilityService,
        private readonly PartnerProgressClient $client
    ) {
    }

    private function partnerCode(): string
    {
        return (string) config('services.partner_startocode.code', 'startocode');
    }

    public function getSnapshotForPreview(User $user): array
    {
        $mapping = $this->eligibilityService->resolveStartocodeMappingForUser($user);
        if (!$mapping) {
            return [
                'eligible' => false,
                'snapshot' => null,
                'status' => 'not_eligible',
                'course_id' => null,
            ];
        }

        $courseId = $mapping->course_id ?: $user->registered_course;
        $snapshot = StudentPartnerProgress::query()
            ->where('user_id', $user->id)
            ->where('partner_code', $this->partnerCode())
            ->where('course_id', $courseId)
            ->latest('id')
            ->first();

        if (!$snapshot) {
            RefreshPartnerProgressJob::dispatch($user->id);
            return [
                'eligible' => true,
                'snapshot' => null,
                'status' => 'syncing',
                'course_id' => $courseId,
            ];
        }

        $refreshMinutes = (int) config('services.partner_startocode.preview_refresh_minutes', 30);
        $lastSyncAgeTooOld = !$snapshot->last_synced_at || $snapshot->last_synced_at->lt(now()->subMinutes($refreshMinutes));
        if ($lastSyncAgeTooOld) {
            RefreshPartnerProgressJob::dispatch($user->id);
        }

        return [
            'eligible' => true,
            'snapshot' => $snapshot,
            'status' => 'ready',
            'course_id' => $courseId,
        ];
    }

    public function syncUser(User $user, bool $force = false): array
    {
        $mapping = $this->eligibilityService->resolveStartocodeMappingForUser($user);
        if (!$mapping) {
            return ['status' => 'not_eligible'];
        }

        $courseId = $mapping->course_id ?: $user->registered_course;
        $omcpId = trim((string) ($user->userId ?? ''));
        if ($omcpId === '') {
            return ['status' => 'missing_omcp_id'];
        }

        $existing = StudentPartnerProgress::query()
            ->where('user_id', $user->id)
            ->where('partner_code', $this->partnerCode())
            ->where('course_id', $courseId)
            ->latest('id')
            ->first();

        $updatedSince = null;
        if (!$force && $existing?->last_synced_at) {
            $updatedSince = $existing->last_synced_at;
        }

        $result = $this->client->fetchStudentProgress($omcpId, $updatedSince);
        if (!$result['ok']) {
            $this->saveSyncFailure($user, $mapping->learning_path_id, $courseId, $omcpId, (string) $result['message']);
            return [
                'status' => 'failed',
                'message' => (string) $result['message'],
                'http_status' => (int) ($result['status'] ?? 0),
            ];
        }

        $payload = $result['payload'] ?? [];
        $data = $payload['data'] ?? [];
        $progress = $data['progress'] ?? [];
        $learningPaths = is_array($progress['learning_paths'] ?? null) ? $progress['learning_paths'] : [];
        $courses = is_array($progress['courses'] ?? null) ? $progress['courses'] : [];
        $allUnits = array_merge($learningPaths, $courses);

        $selected = $this->pickProgressUnit($allUnits, $mapping->learning_path_id);
        $lastActivity = $this->extractLastActivity($allUnits, $selected);
        $overall = $this->calculateOverall($selected);

        $summary = [
            'selected' => $selected,
            'learning_paths_count' => count($learningPaths),
            'courses_count' => count($courses),
            'learning_paths' => $learningPaths,
            'courses' => $courses,
        ];

        $staleDays = (int) config('services.partner_startocode.stale_after_days', 7);
        $staleAfter = $lastActivity ? $lastActivity->copy()->addDays($staleDays) : now()->addDays($staleDays);

        $record = $this->persistSnapshot(
            user: $user,
            courseId: $courseId,
            omcpId: $omcpId,
            learningPathId: $mapping->learning_path_id,
            partnerStudentRef: $data['partner_student_ref'] ?? null,
            summary: $summary,
            rawData: $data,
            selected: $selected,
            overall: $overall,
            lastActivity: $lastActivity,
            staleAfter: $staleAfter
        );

        Log::info('Partner progress synced', [
            'user_id' => $user->id,
            'partner_code' => $this->partnerCode(),
            'course_id' => $courseId,
        ]);

        return ['status' => 'synced', 'snapshot' => $record];
    }

    public function syncBulkItem(string $programSlug, array $item): array
    {
        $omcpId = trim((string) ($item['omcp_id'] ?? $item['external_student_id'] ?? ''));
        if ($omcpId === '') {
            $this->auditUnresolved($programSlug, $item, 'missing_omcp_id');
            return ['status' => 'unresolved', 'reason' => 'missing_omcp_id'];
        }

        $user = User::query()->where('userId', $omcpId)->first();
        if (!$user) {
            $this->auditUnresolved($programSlug, $item, 'user_not_found');
            return ['status' => 'unresolved', 'reason' => 'user_not_found'];
        }

        $mapping = $this->eligibilityService->resolveStartocodeMappingForUser($user);
        if (!$mapping) {
            return ['status' => 'not_eligible'];
        }

        $entryProgress = is_array($item['progress'] ?? null) ? $item['progress'] : [];
        $progressUnits = array_merge(
            is_array($entryProgress['learning_paths'] ?? null) ? $entryProgress['learning_paths'] : [],
            is_array($entryProgress['courses'] ?? null) ? $entryProgress['courses'] : []
        );
        $selected = $this->pickProgressUnit($progressUnits, $mapping->learning_path_id);

        if ($selected === []) {
            $this->auditUnresolved($programSlug, $item, 'missing_progress_data');
            return ['status' => 'unresolved', 'reason' => 'missing_progress_data'];
        }

        $summary = [
            'selected' => $selected,
            'bulk_program_slug' => $programSlug,
            'learning_paths_count' => is_array($entryProgress['learning_paths'] ?? null) ? count($entryProgress['learning_paths']) : 0,
            'courses_count' => is_array($entryProgress['courses'] ?? null) ? count($entryProgress['courses']) : 0,
        ];
        $overall = $this->calculateOverall($selected);
        $lastActivity = $this->extractLastActivity($progressUnits, $selected);
        $staleDays = (int) config('services.partner_startocode.stale_after_days', 7);
        $staleAfter = $lastActivity ? $lastActivity->copy()->addDays($staleDays) : now()->addDays($staleDays);

        $snapshot = $this->persistSnapshot(
            user: $user,
            courseId: $mapping->course_id ?: $user->registered_course,
            omcpId: $omcpId,
            learningPathId: $mapping->learning_path_id,
            partnerStudentRef: (string) ($item['partner_student_ref'] ?? ''),
            summary: $summary,
            rawData: $item,
            selected: $selected,
            overall: $overall,
            lastActivity: $lastActivity,
            staleAfter: $staleAfter
        );

        return ['status' => 'synced', 'snapshot_id' => $snapshot->id];
    }

    private function saveSyncFailure(User $user, ?int $learningPathId, ?int $courseId, string $omcpId, string $error): void
    {
        StudentPartnerProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'partner_code' => $this->partnerCode(),
                'omcp_id' => $omcpId,
                'course_id' => $courseId,
            ],
            [
                'learning_path_id' => $learningPathId,
                'last_sync_attempt_at' => now(),
                'last_sync_error' => $error,
            ]
        );

        Log::warning('Partner progress sync failed', [
            'user_id' => $user->id,
            'partner_code' => $this->partnerCode(),
            'course_id' => $courseId,
            'error' => $error,
        ]);
    }

    private function pickProgressUnit(array $allUnits, ?int $learningPathId): array
    {
        if ($learningPathId) {
            foreach ($allUnits as $unit) {
                if ((int) ($unit['id'] ?? 0) === (int) $learningPathId) {
                    return $unit;
                }
            }
        }

        return $allUnits[0] ?? [];
    }

    private function extractLastActivity(array $allUnits, array $fallbackSelected = []): ?Carbon
    {
        $lastActivity = null;
        foreach ($allUnits as $unit) {
            if (empty($unit['last_activity_at'])) {
                continue;
            }
            try {
                $current = Carbon::parse((string) $unit['last_activity_at']);
                if (!$lastActivity || $current->gt($lastActivity)) {
                    $lastActivity = $current;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        if (!$lastActivity && !empty($fallbackSelected['last_activity_at'])) {
            try {
                $lastActivity = Carbon::parse((string) $fallbackSelected['last_activity_at']);
            } catch (\Throwable) {
            }
        }

        return $lastActivity;
    }

    private function calculateOverall(array $selected): float
    {
        $values = [];
        foreach ($selected as $key => $value) {
            if (is_string($key) && str_ends_with($key, '_percentage_complete') && is_numeric($value)) {
                $values[] = (float) $value;
            }
        }

        if ($values === []) {
            return 0.0;
        }

        return round(array_sum($values) / count($values), 2);
    }

    /** @return array<string, float> */
    private function percentageCompleteSlice(array $selected): array
    {
        $slice = [];
        foreach ($selected as $key => $value) {
            if (is_string($key) && str_ends_with($key, '_percentage_complete') && is_numeric($value)) {
                $slice[$key] = round((float) $value, 4);
            }
        }
        ksort($slice);

        return $slice;
    }

    private function appendHistoryPointIfNeeded(StudentPartnerProgress $snapshot, array $selected, array $raw): void
    {
        $latest = StudentPartnerProgressHistory::query()
            ->where('student_partner_progress_id', $snapshot->id)
            ->latest('captured_at')
            ->first();

        $historyGapHours = (int) config('services.partner_startocode.history_min_gap_hours', 12);
        $isTimeGapSatisfied = !$latest || $latest->captured_at->lte(now()->subHours($historyGapHours));
        $prevMetrics = $latest ? $this->percentageCompleteSlice(
            is_array($latest->payload_json['selected_metrics'] ?? null) ? $latest->payload_json['selected_metrics'] : []
        ) : [];
        $nextMetrics = $this->percentageCompleteSlice($selected);
        $isProgressChanged = !$latest
            || (float) $latest->overall_progress_percent !== (float) ($snapshot->overall_progress_percent ?? 0)
            || json_encode($prevMetrics) !== json_encode($nextMetrics);

        if (!$isTimeGapSatisfied && !$isProgressChanged) {
            return;
        }

        StudentPartnerProgressHistory::create([
            'student_partner_progress_id' => $snapshot->id,
            'user_id' => $snapshot->user_id,
            'partner_code' => $snapshot->partner_code,
            'course_id' => $snapshot->course_id,
            'captured_at' => now(),
            'overall_progress_percent' => $snapshot->overall_progress_percent,
            'video_percentage_complete' => (float) ($selected['video_percentage_complete'] ?? 0),
            'quiz_percentage_complete' => (float) ($selected['quiz_percentage_complete'] ?? 0),
            'project_percentage_complete' => (float) ($selected['project_percentage_complete'] ?? 0),
            'task_percentage_complete' => (float) ($selected['task_percentage_complete'] ?? 0),
            'payload_json' => [
                'selected_metrics' => $selected,
                'raw' => $raw,
            ],
        ]);
    }

    private function persistSnapshot(
        User $user,
        ?int $courseId,
        string $omcpId,
        ?int $learningPathId,
        ?string $partnerStudentRef,
        array $summary,
        array $rawData,
        array $selected,
        float $overall,
        ?Carbon $lastActivity,
        Carbon $staleAfter
    ): StudentPartnerProgress {
        return DB::transaction(function () use ($user, $courseId, $omcpId, $learningPathId, $partnerStudentRef, $summary, $rawData, $selected, $overall, $lastActivity, $staleAfter) {
            $snapshot = StudentPartnerProgress::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'partner_code' => $this->partnerCode(),
                    'omcp_id' => $omcpId,
                    'course_id' => $courseId,
                ],
                [
                    'learning_path_id' => $learningPathId,
                    'partner_student_ref' => $partnerStudentRef,
                    'progress_summary_json' => $summary,
                    'progress_raw_json' => $rawData,
                    'overall_progress_percent' => $overall,
                    'last_activity_at' => $lastActivity,
                    'last_synced_at' => now(),
                    'last_sync_attempt_at' => now(),
                    'stale_after_at' => $staleAfter,
                    'last_sync_error' => null,
                ]
            );

            $this->appendHistoryPointIfNeeded($snapshot, $selected, $rawData);

            return $snapshot;
        });
    }

    private function auditUnresolved(string $programSlug, array $payload, string $reason): void
    {
        PartnerProgressSyncAudit::create([
            'partner_code' => $this->partnerCode(),
            'context' => "program:{$programSlug}",
            'omcp_id' => (string) ($payload['omcp_id'] ?? $payload['external_student_id'] ?? ''),
            'reason' => $reason,
            'payload_json' => $payload,
        ]);
    }
}
