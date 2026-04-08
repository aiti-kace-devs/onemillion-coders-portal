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
     * Format: OMCP-{batch_number}{YY}{NNNNNN}
     * Example: OMCP-126100980
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

        // Generate a unique 6-digit number (100000–999999)
        $maxAttempts = 20;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $number = random_int(100000, 999999);
            $studentId = $prefix . $number;

            if (!User::where('student_id', $studentId)->exists()) {
                return $studentId;
            }
        }

        // Fallback: find the next available number sequentially
        $lastId = User::where('student_id', 'like', "{$prefix}%")
            ->orderByDesc('student_id')
            ->value('student_id');

        $nextNumber = $lastId
            ? ((int) substr($lastId, -6)) + 1
            : 100000;

        $finalId = $prefix . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
        return $finalId;
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
