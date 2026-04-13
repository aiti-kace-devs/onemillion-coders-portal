<?php

namespace App\Services;

use App\Events\CourseBatchCreated;
use App\Models\Batch;
use App\Models\CourseBatch;
use App\Models\Course;
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

        $batchStart  = Carbon::parse($batch->start_date);
        $batchEnd    = Carbon::parse($batch->end_date);
        $durationDays = (int) $programme->duration_in_days;

        $createdBatches = collect();

        DB::transaction(function () use (
            $course, $batch, $batchStart, $batchEnd, $durationDays, &$createdBatches
        ) {
            $cursor = $batchStart->copy();

            while ($cursor->lessThan($batchEnd)) {
                $start = $cursor->copy();
                $end   = $cursor->copy()->addDays($durationDays - 1);

                // Clamp to admission batch end
                if ($end->greaterThan($batchEnd)) {
                    $end = $batchEnd->copy();
                }

                $centre         = $course->centre;
                $availableSlots = $centre
                    ? $this->quotaService->getAvailableSlots(
                        $centre,
                        $course,
                        $start->toDateString(),
                        $end->toDateString()
                    )
                    : 0;

                $programmeBatch = CourseBatch::create([
                    'course_id'       => $course->id,
                    'batch_id'        => $batch->id,
                    'duration'        => $durationDays,
                    'start_date'      => $start->toDateString(),
                    'end_date'        => $end->toDateString(),
                    'available_slots' => $availableSlots,
                ]);

                event(new CourseBatchCreated($programmeBatch));

                $createdBatches->push($programmeBatch);

                $cursor->addDays($durationDays);
            }
        });

        return $createdBatches;
    }

    /**
     * Delete existing programme batches for a course+batch (guards against active admissions)
     * then re-generate them.
     */
    public function regenerateForCourse(Course $course, Batch $batch): Collection
    {
        $existing = CourseBatch::where('course_id', $course->id)
            ->where('batch_id', $batch->id)
            ->get();

        foreach ($existing as $pb) {
            if ($pb->admissions()->exists()) {
                throw new \RuntimeException(
                    "Cannot regenerate — programme batch [{$pb->id}] has active admissions."
                );
            }
        }

        DB::transaction(function () use ($existing) {
            foreach ($existing as $pb) {
                $pb->delete();
            }
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
