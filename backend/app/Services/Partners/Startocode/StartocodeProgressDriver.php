<?php

namespace App\Services\Partners\Startocode;

use App\Services\Partners\Contracts\PartnerProgressDriver;
use App\Support\StartocodePartnerCode;
use Carbon\CarbonInterface;

class StartocodeProgressDriver implements PartnerProgressDriver
{
    /**
     * Default slug when App Config / env is unset; use {@see StartocodePartnerCode::current()} at runtime.
     *
     * @deprecated Prefer {@see StartocodePartnerCode::current()} or {@see StartocodePartnerCode::FALLBACK}
     */
    public const PARTNER_CODE = 'startocode';

    public function __construct(
        private readonly PartnerProgressClient $client
    ) {
    }

    public function code(): string
    {
        return StartocodePartnerCode::current();
    }

    public function fetchStudentProgress(string $omcpId, ?CarbonInterface $updatedSince = null): array
    {
        return $this->client->fetchStudentProgress($this->code(), $omcpId, $updatedSince);
    }

    public function fetchProgramProgressPage(
        string $programSlug,
        int $page = 1,
        int $perPage = 100,
        ?CarbonInterface $updatedSince = null
    ): array {
        return $this->client->fetchProgramProgressPage(
            partnerCode: $this->code(),
            programSlug: $programSlug,
            page: $page,
            perPage: $perPage,
            updatedSince: $updatedSince
        );
    }

    public function normalizeSinglePayload(array $payload): array
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $progress = is_array($data['progress'] ?? null) ? $data['progress'] : [];

        $learningPaths = is_array($progress['learning_paths'] ?? null) ? $progress['learning_paths'] : [];
        $courses = is_array($progress['courses'] ?? null) ? $progress['courses'] : [];
        $units = array_merge($learningPaths, $courses);

        // summary mirrors what UI expects; selected will be computed by the sync service
        $summary = [
            'learning_paths_count' => count($learningPaths),
            'courses_count' => count($courses),
            'learning_paths' => $learningPaths,
            'courses' => $courses,
        ];

        return [
            'partner_student_ref' => $data['partner_student_ref'] ?? null,
            'units' => $units,
            'summary' => $summary,
            'raw' => $data,
        ];
    }

    public function normalizeBulkItem(array $item, string $programSlug): array
    {
        $omcpId = (string) ($item['omcp_id'] ?? $item['external_student_id'] ?? '');
        $partnerStudentRef = $item['partner_student_ref'] ?? null;

        // Doc shape: bulk item has a single `learning_path` object
        $learningPath = is_array($item['learning_path'] ?? null) ? $item['learning_path'] : [];
        $units = $learningPath !== [] ? [$learningPath] : [];

        // Fallback: some partner implementations may include single-student shape under `progress`.
        $entryProgress = is_array($item['progress'] ?? null) ? $item['progress'] : [];
        if ($units === [] && $entryProgress !== []) {
            $units = array_merge(
                is_array($entryProgress['learning_paths'] ?? null) ? $entryProgress['learning_paths'] : [],
                is_array($entryProgress['courses'] ?? null) ? $entryProgress['courses'] : []
            );
        }

        $summary = [
            'bulk_program_slug' => $programSlug,
            'learning_paths_count' => $learningPath !== [] ? 1 : (is_array($entryProgress['learning_paths'] ?? null) ? count($entryProgress['learning_paths']) : 0),
            'courses_count' => is_array($entryProgress['courses'] ?? null) ? count($entryProgress['courses']) : 0,
        ];

        return [
            'omcp_id' => $omcpId,
            'partner_student_ref' => $partnerStudentRef,
            'units' => $units,
            'summary' => $summary,
            'raw' => $item,
        ];
    }
}

