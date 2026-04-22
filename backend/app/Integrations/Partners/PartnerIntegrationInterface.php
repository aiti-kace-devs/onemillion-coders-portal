<?php

namespace App\Integrations\Partners;

use App\Models\User;
use App\Models\Programme;

interface PartnerIntegrationInterface
{
    /**
     * Admit a student to the partner platform.
     */
    public function admitStudent(User $user, Programme $programme): bool | array;

    /**
     * Get the login URL or portal for the student on the partner platform.
     */
    public function loginStudent(User $user): string;

    /**
     * Get the student's progress for a specific programme.
     * 
     * @return array ['percentage' => float, 'last_activity' => DateTime, ...]
     */
    public function getStudentProgress(User $user, Programme $programme): array;

    /**
     * Get overall progress data for a programme.
     */
    public function getProgrammeProgress(Programme $programme): array;

    /**
     * Get a link to course materials on the partner platform.
     */
    public function getCourseMaterialsLink(Programme $programme): string;

    /**
     * Get the external user ID.
     */
    public function getExternalUserId(): string;
}
