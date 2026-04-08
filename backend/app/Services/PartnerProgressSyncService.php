<?php

namespace App\Services;

use App\Jobs\RefreshPartnerProgressJob;
use App\Models\PartnerIntegration;
use App\Models\PartnerProgressSyncAudit;
use App\Models\StudentPartnerProgress;
use App\Models\StudentPartnerProgressHistory;
use App\Models\User;
use App\Support\PartnerCodeNormalizer;
use App\Support\StartocodePartnerCode;
use App\Services\Partners\PartnerProgressPayloadValidator;
use App\Services\Partners\PartnerRegistry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PartnerProgressSyncService
{
    public function __construct(
        private readonly PartnerCourseEligibilityService $eligibilityService,
        private readonly PartnerRegistry $partners,
        private readonly PartnerProgressPayloadValidator $payloadValidator
    ) {
    }

    private function resolveMapping(User $user, ?string $partnerCode = null)
    {
        if (is_string($partnerCode) && trim($partnerCode) !== '') {
            return $this->eligibilityService->resolveMappingForUser($user, trim($partnerCode));
        }

        return $this->eligibilityService->resolveAnyMappingForUser($user);
    }

    private function partnerConfig(string $partnerCode, string $key, mixed $default = null): mixed
    {
        if ($key === 'stale_after_days') {
            $fromApp = config('PARTNER_PROGRESS_STALE_AFTER_DAYS');
            if ($fromApp !== null && $fromApp !== '') {
                return (int) $fromApp;
            }
        }

        if (! $this->partners->has($partnerCode)) {
            return $default;
        }

        return config("services.partner_progress.{$key}", $default);
    }

    public function getSnapshotForPreview(User $user, ?string $partnerCode = null): array
    {
        $mapping = $this->resolveMapping($user, $partnerCode);
        if (!$mapping) {
            return [
                'eligible' => false,
                'snapshot' => null,
                'status' => 'not_eligible',
                'course_id' => null,
                'partner_code' => null,
                'mapping_id' => null,
                'message' => null,
            ];
        }

        $courseId = $mapping->course_id ?: $user->registered_course;
        if (!$courseId) {
            return [
                'eligible' => false,
                'snapshot' => null,
                'status' => 'not_eligible',
                'course_id' => null,
                'partner_code' => $partnerCode,
                'mapping_id' => $mapping->id,
                'message' => 'Missing course mapping for partner progress.',
            ];
        }
        $partnerCode = (string) $mapping->partner_code;

        if (!$this->partners->has($partnerCode)) {
            return [
                'eligible' => false,
                'snapshot' => null,
                'status' => 'not_eligible',
                'course_id' => $courseId,
                'partner_code' => $partnerCode,
                'mapping_id' => $mapping->id,
                'message' => "Partner driver not configured for '{$partnerCode}'.",
            ];
        }

        $snapshot = StudentPartnerProgress::query()
            ->where('user_id', $user->id)
            ->where('partner_code', $partnerCode)
            ->where('course_id', $courseId)
            ->latest('id')
            ->first();

        if (!$snapshot) {
            RefreshPartnerProgressJob::dispatch($user->id, false, $partnerCode);
            return [
                'eligible' => true,
                'snapshot' => null,
                'status' => 'syncing',
                'course_id' => $courseId,
                'partner_code' => $partnerCode,
                'mapping_id' => $mapping->id,
                'message' => null,
            ];
        }

        $refreshMinutes = (int) $this->partnerConfig($partnerCode, 'preview_refresh_minutes', 30);
        $syncAttemptAgeTooOld = !$snapshot->last_sync_attempt_at || $snapshot->last_sync_attempt_at->lt(now()->subMinutes($refreshMinutes));

        if (!$snapshot->last_synced_at) {
            if ($syncAttemptAgeTooOld) {
                RefreshPartnerProgressJob::dispatch($user->id, false, $partnerCode);
            }

            $hasError = (string) ($snapshot->last_sync_error ?? '') !== '';

            return [
                'eligible' => true,
                'snapshot' => $snapshot,
                'status' => $hasError ? 'failed' : 'syncing',
                'course_id' => $courseId,
                'partner_code' => $partnerCode,
                'mapping_id' => $mapping->id,
                'message' => $hasError ? (string) $snapshot->last_sync_error : null,
            ];
        }

        $lastSyncAgeTooOld = $snapshot->last_synced_at->lt(now()->subMinutes($refreshMinutes));
        if ($lastSyncAgeTooOld && $syncAttemptAgeTooOld) {
            RefreshPartnerProgressJob::dispatch($user->id, false, $partnerCode);
        }

        return [
            'eligible' => true,
            'snapshot' => $snapshot,
            'status' => 'ready',
            'course_id' => $courseId,
            'partner_code' => $partnerCode,
            'mapping_id' => $mapping->id,
            'message' => null,
        ];
    }

    public function syncUser(User $user, bool $force = false, ?string $partnerCode = null): array
    {
        $mapping = $this->resolveMapping($user, $partnerCode);
        if (!$mapping) {
            return ['status' => 'not_eligible'];
        }

        $partnerCode = (string) $mapping->partner_code;
        if (!$this->partners->has($partnerCode)) {
            return ['status' => 'not_eligible', 'message' => "Partner driver not configured for '{$partnerCode}'."];
        }

        $courseId = $mapping->course_id ?: $user->registered_course;
        if (!$courseId) {
            return ['status' => 'not_eligible', 'message' => 'missing_course_id'];
        }
        $omcpId = trim((string) $user->partnerProgressExternalIdentifier($partnerCode));
        if ($omcpId === '') {
            return ['status' => 'missing_omcp_id'];
        }

        $existing = StudentPartnerProgress::query()
            ->where('user_id', $user->id)
            ->where('partner_code', $partnerCode)
            ->where('course_id', $courseId)
            ->latest('id')
            ->first();

        $updatedSince = null;
        if (!$force && $existing?->last_synced_at) {
            $updatedSince = $existing->last_synced_at;
        }

        $driver = $this->partners->get($partnerCode);
        $result = $driver->fetchStudentProgress($omcpId, $updatedSince);
        if (!$result['ok']) {
            $this->saveSyncFailure($user, $partnerCode, $mapping->learning_path_id, $courseId, $omcpId, (string) $result['message']);
            return [
                'status' => 'failed',
                'message' => (string) $result['message'],
                'http_status' => (int) ($result['status'] ?? 0),
            ];
        }

        $payload = is_array($result['payload'] ?? null) ? $result['payload'] : [];

        $integration = PartnerIntegration::query()->where('partner_code', $partnerCode)->first();
        $contract = is_array($integration?->validation_contract_json ?? null) ? $integration->validation_contract_json : null;

        try {
            $normalized = $driver->normalizeSinglePayload($payload);
        } catch (\Throwable $e) {
            $this->auditContractFailure($partnerCode, $omcpId, 'normalize_exception', [
                'exception' => $e->getMessage(),
            ]);
            $this->saveSyncFailure($user, $partnerCode, $mapping->learning_path_id, $courseId, $omcpId, 'Progress normalize failed: '.$e->getMessage());

            return [
                'status' => 'failed',
                'message' => 'Progress normalize failed: '.$e->getMessage(),
                'http_status' => 0,
            ];
        }

        $validationError = $this->payloadValidator->validateSingleNormalized($normalized, $contract);
        if ($validationError !== null) {
            $this->auditContractFailure($partnerCode, $omcpId, 'contract_validation_failed', [
                'message' => $validationError,
                'normalized' => $normalized,
            ]);
            $this->saveSyncFailure($user, $partnerCode, $mapping->learning_path_id, $courseId, $omcpId, $validationError);

            return [
                'status' => 'failed',
                'message' => $validationError,
                'http_status' => 0,
            ];
        }

        $units = is_array($normalized['units'] ?? null) ? $normalized['units'] : [];

        $selected = $this->pickProgressUnit($units, $mapping->learning_path_id);
        $lastActivity = $this->extractLastActivity($units, $selected);
        $overall = $this->calculateOverall($selected);

        $summaryBase = is_array($normalized['summary'] ?? null) ? $normalized['summary'] : [];
        $summary = [
            ...$summaryBase,
            'selected' => $selected,
        ];

        $staleDays = (int) $this->partnerConfig($partnerCode, 'stale_after_days', 7);
        $staleAfter = $lastActivity ? $lastActivity->copy()->addDays($staleDays) : now()->addDays($staleDays);

        $record = $this->persistSnapshot(
            user: $user,
            partnerCode: $partnerCode,
            courseId: $courseId,
            omcpId: $omcpId,
            learningPathId: $mapping->learning_path_id,
            partnerStudentRef: $normalized['partner_student_ref'] ?? null,
            summary: $summary,
            rawData: is_array($normalized['raw'] ?? null) ? $normalized['raw'] : [],
            selected: $selected,
            overall: $overall,
            lastActivity: $lastActivity,
            staleAfter: $staleAfter
        );

        Log::info('Partner progress synced', [
            'user_id' => $user->id,
            'partner_code' => $partnerCode,
            'course_id' => $courseId,
        ]);

        return ['status' => 'synced', 'snapshot' => $record];
    }

    public function syncBulkItem(string $programSlug, array $item, string $partnerCode): array
    {
        if (!$this->partners->has($partnerCode)) {
            return ['status' => 'not_eligible', 'reason' => 'partner_driver_missing'];
        }

        $driver = $this->partners->get($partnerCode);

        $integration = PartnerIntegration::query()->where('partner_code', $partnerCode)->first();
        $contract = is_array($integration?->validation_contract_json ?? null) ? $integration->validation_contract_json : null;

        try {
            $normalized = $driver->normalizeBulkItem($item, $programSlug);
        } catch (\Throwable $e) {
            $this->auditContractFailure($partnerCode, (string) ($item['omcp_id'] ?? $item['external_student_id'] ?? ''), 'bulk_normalize_exception', [
                'exception' => $e->getMessage(),
                'item' => $item,
            ]);

            return ['status' => 'unresolved', 'reason' => 'normalize_exception'];
        }

        $bulkValidationError = $this->payloadValidator->validateBulkNormalized($normalized, $contract);
        if ($bulkValidationError !== null) {
            $this->auditContractFailure($partnerCode, (string) ($normalized['omcp_id'] ?? ''), 'bulk_contract_validation_failed', [
                'message' => $bulkValidationError,
                'normalized' => $normalized,
            ]);

            return ['status' => 'unresolved', 'reason' => 'contract_validation_failed'];
        }

        $omcpId = trim((string) ($normalized['omcp_id'] ?? ''));
        if ($omcpId === '') {
            $this->auditUnresolved($partnerCode, $programSlug, $item, 'missing_omcp_id');
            return ['status' => 'unresolved', 'reason' => 'missing_omcp_id'];
        }

        $user = $this->findUserForPartnerBulkOmcpId($partnerCode, $omcpId);
        if (! $user) {
            $this->auditUnresolved($partnerCode, $programSlug, $item, 'user_not_found');
            return ['status' => 'unresolved', 'reason' => 'user_not_found'];
        }

        $mapping = $this->eligibilityService->resolveMappingForUser($user, $partnerCode);
        if (!$mapping) {
            return ['status' => 'not_eligible'];
        }

        $units = is_array($normalized['units'] ?? null) ? $normalized['units'] : [];
        $selected = $this->pickProgressUnit($units, $mapping->learning_path_id);

        if ($selected === []) {
            $this->auditUnresolved($partnerCode, $programSlug, $item, 'missing_progress_data');
            return ['status' => 'unresolved', 'reason' => 'missing_progress_data'];
        }

        $summaryBase = is_array($normalized['summary'] ?? null) ? $normalized['summary'] : [];
        $summary = [
            ...$summaryBase,
            'selected' => $selected,
        ];
        $overall = $this->calculateOverall($selected);
        $lastActivity = $this->extractLastActivity($units, $selected);
        $staleDays = (int) $this->partnerConfig($partnerCode, 'stale_after_days', 7);
        $staleAfter = $lastActivity ? $lastActivity->copy()->addDays($staleDays) : now()->addDays($staleDays);

        $courseId = $mapping->course_id ?: $user->registered_course;
        if (!$courseId) {
            return ['status' => 'not_eligible', 'reason' => 'missing_course_id'];
        }

        $snapshot = $this->persistSnapshot(
            user: $user,
            partnerCode: $partnerCode,
            courseId: $courseId,
            omcpId: $omcpId,
            learningPathId: $mapping->learning_path_id,
            partnerStudentRef: (string) ($normalized['partner_student_ref'] ?? ''),
            summary: $summary,
            rawData: is_array($normalized['raw'] ?? null) ? $normalized['raw'] : [],
            selected: $selected,
            overall: $overall,
            lastActivity: $lastActivity,
            staleAfter: $staleAfter
        );

        return ['status' => 'synced', 'snapshot_id' => $snapshot->id];
    }

    private function saveSyncFailure(User $user, string $partnerCode, ?int $learningPathId, ?int $courseId, string $omcpId, string $error): void
    {
        StudentPartnerProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'partner_code' => $partnerCode,
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
            'partner_code' => $partnerCode,
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

        $historyGapHours = (int) config('services.partner_progress.history_min_gap_hours', 12);
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
            // Legacy fixed metric columns are intentionally left null.
            // Canonical analytics and charting now use payload_json.selected_metrics only.
            'video_percentage_complete' => null,
            'quiz_percentage_complete' => null,
            'project_percentage_complete' => null,
            'task_percentage_complete' => null,
            'payload_json' => [
                'selected_metrics' => $selected,
                'raw' => $raw,
            ],
        ]);
    }

    private function persistSnapshot(
        User $user,
        string $partnerCode,
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
        return DB::transaction(function () use ($user, $partnerCode, $courseId, $omcpId, $learningPathId, $partnerStudentRef, $summary, $rawData, $selected, $overall, $lastActivity, $staleAfter) {
            $snapshot = StudentPartnerProgress::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'partner_code' => $partnerCode,
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

    private function auditUnresolved(string $partnerCode, string $programSlug, array $payload, string $reason): void
    {
        PartnerProgressSyncAudit::create([
            'partner_code' => $partnerCode,
            'context' => "program:{$programSlug}",
            'omcp_id' => (string) ($payload['omcp_id'] ?? $payload['external_student_id'] ?? ''),
            'reason' => $reason,
            'payload_json' => $payload,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payloadJson
     */
    private function auditContractFailure(string $partnerCode, string $omcpId, string $reason, array $payloadJson): void
    {
        PartnerProgressSyncAudit::create([
            'partner_code' => $partnerCode,
            'context' => 'contract',
            'omcp_id' => $omcpId,
            'reason' => $reason,
            'payload_json' => $payloadJson,
        ]);
    }

    /**
     * Resolve bulk-feed learner id to a User. Prefer {@code userId}; for Startocode also match {@code student_id} or numeric {@code id}.
     */
    private function findUserForPartnerBulkOmcpId(string $partnerCode, string $omcpId): ?User
    {
        $code = PartnerCodeNormalizer::normalize($partnerCode);
        $omcpId = trim($omcpId);
        if ($omcpId === '') {
            return null;
        }

        $byUserId = User::query()->where('userId', $omcpId)->first();
        if ($byUserId) {
            return $byUserId;
        }

        if (! StartocodePartnerCode::matches($partnerCode)) {
            return null;
        }

        if (Schema::hasColumn('users', 'student_id')) {
            $byStudentId = User::query()->where('student_id', $omcpId)->first();
            if ($byStudentId) {
                return $byStudentId;
            }
        }

        if (ctype_digit($omcpId)) {
            return User::query()->where('id', (int) $omcpId)->first();
        }

        return null;
    }
}
