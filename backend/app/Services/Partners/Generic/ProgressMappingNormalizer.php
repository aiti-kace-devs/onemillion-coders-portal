<?php

namespace App\Services\Partners\Generic;

/**
 * Maps arbitrary partner JSON into the internal progress DTO consumed by {@see \App\Services\PartnerProgressSyncService}.
 *
 * Contract keys in config are OMCP-oriented (where to read external fields), not vendor field names.
 * Defaults match the bundled REST shape; override via partner_integrations.response_mapping_json.
 */
class ProgressMappingNormalizer
{
    /**
     * @param  array<string, mixed>|null  $responseMapping  merged single_student + bulk_item from DB
     * @return array{partner_student_ref: ?string, units: array<int, array>, summary: array, raw: array}
     */
    public function normalizeSinglePayload(array $payload, ?array $responseMapping): array
    {
        $defaults = (array) config('services.partner_progress_mapping_defaults.single_student', []);
        $single = array_replace_recursive(
            $defaults,
            is_array($responseMapping['single_student'] ?? null) ? $responseMapping['single_student'] : []
        );

        $dataRoot = trim((string) ($single['data_root'] ?? 'data'));
        $data = $dataRoot === '' ? $payload : data_get($payload, $dataRoot);
        if (! is_array($data)) {
            $data = [];
        }

        $progressRoot = trim((string) ($single['progress_root'] ?? 'progress'));
        $progress = $progressRoot === '' ? [] : data_get($data, $progressRoot);
        if (! is_array($progress)) {
            $progress = [];
        }

        $lpKey = (string) ($single['learning_paths_key'] ?? 'learning_paths');
        $cKey = (string) ($single['courses_key'] ?? 'courses');
        $learningPaths = is_array($progress[$lpKey] ?? null) ? $progress[$lpKey] : [];
        $courses = is_array($progress[$cKey] ?? null) ? $progress[$cKey] : [];
        $units = array_merge($learningPaths, $courses);

        $extRefPath = (string) ($single['external_student_ref_path'] ?? 'partner_student_ref');
        $partnerStudentRef = data_get($data, $extRefPath);

        $summary = [
            'learning_paths_count' => count($learningPaths),
            'courses_count' => count($courses),
            'learning_paths' => $learningPaths,
            'courses' => $courses,
        ];

        $rawPath = trim((string) ($single['raw_snapshot_path'] ?? 'data'));
        $raw = $rawPath === '' ? $payload : (is_array(data_get($payload, $rawPath)) ? data_get($payload, $rawPath) : $data);

        return [
            'partner_student_ref' => is_string($partnerStudentRef) || is_numeric($partnerStudentRef) ? (string) $partnerStudentRef : null,
            'units' => $units,
            'summary' => $summary,
            'raw' => is_array($raw) ? $raw : [],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $responseMapping
     * @return array{omcp_id: string, partner_student_ref: ?string, units: array<int, array>, summary: array, raw: array}
     */
    public function normalizeBulkItem(array $item, string $programSlug, ?array $responseMapping): array
    {
        $defaults = (array) config('services.partner_progress_mapping_defaults.bulk_item', []);
        $bulk = array_replace_recursive(
            $defaults,
            is_array($responseMapping['bulk_item'] ?? null) ? $responseMapping['bulk_item'] : []
        );

        $keyPaths = $bulk['internal_learner_key_paths'] ?? ['omcp_id', 'external_student_id'];
        if (! is_array($keyPaths)) {
            $keyPaths = ['omcp_id', 'external_student_id'];
        }
        $omcpId = '';
        foreach ($keyPaths as $path) {
            if (! is_string($path) || trim($path) === '') {
                continue;
            }
            $v = data_get($item, $path);
            if ($v !== null && $v !== '') {
                $omcpId = (string) $v;
                break;
            }
        }

        $extRefPath = (string) ($bulk['external_student_ref_path'] ?? 'partner_student_ref');
        $partnerStudentRef = data_get($item, $extRefPath);

        $singleUnitPath = trim((string) ($bulk['single_unit_path'] ?? 'learning_path'));
        $learningPath = $singleUnitPath !== '' && is_array(data_get($item, $singleUnitPath))
            ? data_get($item, $singleUnitPath)
            : [];
        $units = $learningPath !== [] ? [$learningPath] : [];

        $progressRoot = trim((string) ($bulk['progress_root'] ?? 'progress'));
        $entryProgress = $progressRoot === '' ? [] : data_get($item, $progressRoot);
        if (! is_array($entryProgress)) {
            $entryProgress = [];
        }

        $lpKey = (string) ($bulk['learning_paths_key'] ?? 'learning_paths');
        $cKey = (string) ($bulk['courses_key'] ?? 'courses');

        if ($units === [] && $entryProgress !== []) {
            $units = array_merge(
                is_array($entryProgress[$lpKey] ?? null) ? $entryProgress[$lpKey] : [],
                is_array($entryProgress[$cKey] ?? null) ? $entryProgress[$cKey] : []
            );
        }

        $summary = [
            'bulk_program_slug' => $programSlug,
            'learning_paths_count' => $learningPath !== [] ? 1 : (is_array($entryProgress[$lpKey] ?? null) ? count($entryProgress[$lpKey]) : 0),
            'courses_count' => is_array($entryProgress[$cKey] ?? null) ? count($entryProgress[$cKey]) : 0,
        ];

        return [
            'omcp_id' => $omcpId,
            'partner_student_ref' => is_string($partnerStudentRef) || is_numeric($partnerStudentRef) ? (string) $partnerStudentRef : null,
            'units' => $units,
            'summary' => $summary,
            'raw' => $item,
        ];
    }
}
