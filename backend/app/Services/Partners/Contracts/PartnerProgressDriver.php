<?php

namespace App\Services\Partners\Contracts;

use Carbon\CarbonInterface;

interface PartnerProgressDriver
{
    /**
     * Stable logical partner identifier used in DB `partner_code`.
     */
    public function code(): string;

    /**
     * Fetch progress for a single student.
     *
     * Expected return (array shape):
     * - ok: bool
     * - status: int
     * - retryable: bool
     * - message: string
     * - payload: array|null
     */
    public function fetchStudentProgress(string $omcpId, ?CarbonInterface $updatedSince = null): array;

    /**
     * Fetch one paginated page of a program bulk progress feed.
     *
     * Expected return (array shape):
     * - ok: bool
     * - status: int
     * - retryable: bool
     * - message: string
     * - payload: array|null
     * - items: array (when ok)
     * - pagination: array (when ok)
     */
    public function fetchProgramProgressPage(
        string $programSlug,
        int $page = 1,
        int $perPage = 100,
        ?CarbonInterface $updatedSince = null
    ): array;

    /**
     * Normalize a successful single-student API payload into the OMCP internal progress DTO.
     *
     * Return shape:
     * - partner_student_ref: string|null (external partner id)
     * - units: array<int, array>  (learning_paths + courses merged)
     * - summary: array            (summary fields; sync service injects `selected`)
     * - raw: array                (debug snapshot; not vendor-specific schema names)
     */
    public function normalizeSinglePayload(array $payload): array;

    /**
     * Normalize one bulk item into the OMCP internal progress DTO.
     *
     * Return shape:
     * - omcp_id: string (OMCP learner `userId` / internal key)
     * - partner_student_ref: string|null
     * - units: array<int, array>
     * - summary: array
     * - raw: array
     */
    public function normalizeBulkItem(array $item, string $programSlug): array;
}

