<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Course;
use App\Models\User;

class StudentIdGenerator
{
    /**
     * Generate a student ID for a user being admitted.
     *
     * Format: OMCP-{batch_number}{YY}{id}
     * Example: OMCP-125143901
     *
     * @param  User  $user
     * @return string|null  Returns null if batch info is unavailable.
     */
    public static function generate(User $user, ?Course $course = null): ?string
    {
        $course = $course ?? Course::find($user->registered_course);

        if (!$course || !$course->batch_id) {
            return null;
        }

        $batch = Batch::find($course->batch_id);

        if (!$batch) {
            return null;
        }

        $batchNumber = $batch->batch_number;
        $yearSuffix = substr((string) $batch->year, -2);

        // If batch_number was never set (legacy batches), derive it
        if (empty($batchNumber)) {
            $batchNumber = static::deriveBatchNumber($batch);
        }

        $prefix = "OMCP-{$batchNumber}{$yearSuffix}";
        $studentId = $prefix . str($user->id)->padLeft(6, '0');

        return $studentId;
    }

    /**
     * Derive batch_number for legacy batches that don't have one set.
     */
    private static function deriveBatchNumber(Batch $batch): int
    {
        $position = Batch::where('year', $batch->year)
            ->where('id', '<=', $batch->id)
            ->count();

        // Save it for future use
        $batch->batch_number = $position;
        $batch->saveQuietly();

        return $position;
    }
}
