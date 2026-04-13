<?php

namespace App\Services;

use App\Events\CourseBatchCreated;
use App\Models\Batch;
use App\Models\CourseBatch;
use App\Models\Course;
use App\Models\UserAdmission;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CourseBatchService
{
    public function __construct(private QuotaService $quotaService) {}

    /**
     * Generate continuous programme batches for a course within an admission batch period.
     * No gaps between batches. Each batch duration = programme->duration_in_days.
     */
    public function generateForCourse(Course $course, Batch $batch): Collection
    {
        // Ensure relationships are loaded before entering the transaction loop
        $course->loadMissing(['programme', 'centre']);
        $programme = $course->programme;

        if (!$programme || !$programme->duration_in_days) {
            throw new \RuntimeException(
                "Programme for course [{$course->id}] has no duration_in_days set."
            );
        }

        if (!$batch->start_date || !$batch->end_date) {
            throw new \RuntimeException(
                "Admission batch [{$batch->id}] is missing start_date or end_date."
            );
        }

        $batchStart   = Carbon::parse($batch->start_date);
        $batchEnd     = Carbon::parse($batch->end_date);
        $durationDays = (int) $programme->duration_in_days;
        $centre       = $course->centre;

        // Compute available slots once for the full batch window — underlying long-course
        // data is stable throughout this generation run.
        $availableSlots = $centre
            ? $this->quotaService->getAvailableSlots(
                $centre,
                $course,
                $batchStart->toDateString(),
                $batchEnd->toDateString()
            )
            : 0;

        $createdBatches = collect();

        DB::transaction(function () use (
            $course, $batch, $batchStart, $batchEnd, $durationDays, $availableSlots, &$createdBatches
        ) {
            $cursor = $batchStart->copy();

            while ($cursor->lessThan($batchEnd)) {
                $start = $cursor->copy();
                $end   = $cursor->copy()->addDays($durationDays - 1);

                if ($end->greaterThan($batchEnd)) {
                    $end = $batchEnd->copy();
                }

                $programmeBatch = CourseBatch::create([
                    'course_id'       => $course->id,
                    'batch_id'        => $batch->id,
                    'duration'        => $durationDays,
                    'start_date'      => $start->toDateString(),
                    'end_date'        => $end->toDateString(),
                    'available_slots' => $availableSlots,
                ]);

                $createdBatches->push($programmeBatch);

                $cursor->addDays($durationDays);
            }
        });

        // Fire events after the transaction commits so listeners see persisted rows
        foreach ($createdBatches as $programmeBatch) {
            event(new CourseBatchCreated($programmeBatch));
        }

        return $createdBatches;
    }

    /**
     * Delete existing programme batches (guards against active admissions) then re-generate.
     * The entire delete + generate runs in a single transaction.
     */
    public function regenerateForCourse(Course $course, Batch $batch): Collection
    {
        $existing = CourseBatch::where('course_id', $course->id)
            ->where('batch_id', $batch->id)
            ->pluck('id');

        if (UserAdmission::whereIn('programme_batch_id', $existing)->exists()) {
            throw new \RuntimeException(
                "Cannot regenerate — one or more programme batches for this course have active admissions."
            );
        }

        DB::transaction(function () use ($existing) {
            CourseBatch::whereIn('id', $existing)->delete();
        });

        return $this->generateForCourse($course, $batch);
    }

    /**
     * Validate that a CourseBatch's dates fall within its parent admission batch range.
     */
    public function validateDatesWithinBatch(CourseBatch $cb): bool
    {
        $batch = $cb->batch;
        if (!$batch) {
            return false;
        }

        $batchStart = Carbon::parse($batch->start_date);
        $batchEnd   = Carbon::parse($batch->end_date);
        $cbStart    = Carbon::parse($cb->start_date);
        $cbEnd      = Carbon::parse($cb->end_date);

        return $cbStart->greaterThanOrEqualTo($batchStart)
            && $cbEnd->lessThanOrEqualTo($batchEnd)
            && $cbStart->lessThanOrEqualTo($cbEnd);
    }
}
