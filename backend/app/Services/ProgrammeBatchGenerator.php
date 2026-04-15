<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Programme;
use App\Models\ProgrammeBatch;
use App\Events\ProgrammeBatchCreated;
use App\Helpers\SchoolDayCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProgrammeBatchGenerator
{
    /**
     * Generate programme batches for an admission batch.
     *
     * Since ProgrammeBatch is no longer centre-specific, this generates
     * time-sliced batches per programme within the admission window.
     *
     * @param Batch $admissionBatch
     * @param Programme|null $programme  If null, generate for all programmes linked to the batch
     * @return Collection
     */
    public function generate(Batch $admissionBatch, ?Programme $programme = null): Collection
    {
        $generated = collect();
        $newlyCreated = collect();

        $programmes = $programme ? collect([$programme]) : $admissionBatch->programmes;

        foreach ($programmes as $prog) {
            if (!$prog->duration_in_days) {
                continue;
            }

            [$all, $new] = $this->generateForProgramme($admissionBatch, $prog);
            $generated = $generated->merge($all);
            $newlyCreated = $newlyCreated->merge($new);
        }

        if ($newlyCreated->isNotEmpty()) {
            event(new ProgrammeBatchCreated($admissionBatch, $newlyCreated));
        }

        return $generated;
    }

    /**
     * Generate continuous, non-overlapping batches for a single (admissionBatch × programme).
     *
     * @return array{0: Collection, 1: Collection} [allBatches, newlyCreatedBatches]
     */
    private function generateForProgramme(Batch $admissionBatch, Programme $programme): array
    {
        $admissionStart = Carbon::parse($admissionBatch->start_date);
        $admissionEnd = Carbon::parse($admissionBatch->end_date);

        // Total school days (Mon–Fri) in the admission window
        $admissionBatchSchoolDays = SchoolDayCalculator::count($admissionStart, $admissionEnd);
        $admissionBatchWeeks = (int) ceil($admissionBatchSchoolDays / 5);

        // Weeks needed per programme batch
        $programmeWeeks = (int) ceil($programme->duration_in_days / 5);

        if ($programmeWeeks <= 0 || $admissionBatchWeeks < $programmeWeeks) {
            return [collect(), collect()];
        }

        // Number of complete batches that fit
        $count = (int) floor($admissionBatchWeeks / $programmeWeeks);

        if ($count <= 0) {
            return [collect(), collect()];
        }

        $all = collect();
        $newlyCreated = collect();
        $currentStart = $admissionStart->copy();

        for ($i = 0; $i < $count; $i++) {
            // Calculate end date: programmeWeeks * 5 school days from start
            $schoolDaysNeeded = $programmeWeeks * 5;
            $currentEnd = SchoolDayCalculator::add($currentStart, $schoolDaysNeeded - 1);

            // Ensure we don't exceed admission end date
            if ($currentEnd->gt($admissionEnd)) {
                break;
            }

            // Check idempotency — skip if batch with same key exists
            $existing = ProgrammeBatch::where('admission_batch_id', $admissionBatch->id)
                ->where('programme_id', $programme->id)
                ->where('start_date', $currentStart->format('Y-m-d'))
                ->first();

            if (!$existing) {
                $batch = ProgrammeBatch::create([
                    'admission_batch_id' => $admissionBatch->id,
                    'programme_id' => $programme->id,
                    'start_date' => $currentStart,
                    'end_date' => $currentEnd,
                    'status' => true,
                ]);
                $all->push($batch);
                $newlyCreated->push($batch);
            } else {
                $all->push($existing);
            }

            // Next batch starts the next school day after this one ends
            $currentStart = SchoolDayCalculator::add($currentEnd, 1);
        }

        return [$all, $newlyCreated];
    }
}
