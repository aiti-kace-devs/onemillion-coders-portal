<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Centre;
use App\Models\Programme;
use App\Models\ProgrammeBatch;
use App\Events\ProgrammeBatchCreated;
use App\Helpers\SchoolDayCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProgrammeBatchGenerator
{
    /**
     * Generate programme batches for an admission batch.
     *
     * @param Batch $admissionBatch
     * @param Programme|null $programme  If null, generate for all programmes linked to the batch
     * @param Centre|null $centre        If null, generate for all centres linked to the batch
     * @return Collection
     */
    public function generate(Batch $admissionBatch, ?Programme $programme = null, ?Centre $centre = null): Collection
    {
        $generated = collect();

        $centres = $centre ? collect([$centre]) : $admissionBatch->centres;
        $programmes = $programme ? collect([$programme]) : $admissionBatch->programmes;

        foreach ($centres as $centre) {
            foreach ($programmes as $programme) {
                if (!$programme->duration_in_days) {
                    continue;
                }

                $batches = $this->generateForPair($admissionBatch, $programme, $centre);
                $generated = $generated->merge($batches);
            }
        }

        if ($created->isNotEmpty()) {
            // Only fire the event with genuinely newly created batches
            event(new ProgrammeBatchCreated($admissionBatch, $created));
        }

        return $generated;
    }

    /**
     * Generate continuous, non-overlapping batches for a single (batch × programme × centre).
     */
    private function generateForPair(Batch $admissionBatch, Programme $programme, Centre $centre): Collection
    {
        $admissionStart = Carbon::parse($admissionBatch->start_date);
        $admissionEnd = Carbon::parse($admissionBatch->end_date);

        // Total school days (Mon–Fri) in the admission window
        $admissionBatchSchoolDays = SchoolDayCalculator::count($admissionStart, $admissionEnd);
        $admissionBatchWeeks = (int) ceil($admissionBatchSchoolDays / 5);

        // Weeks needed per programme batch
        $programmeWeeks = (int) ceil($programme->duration_in_days / 5);

        if ($programmeWeeks <= 0 || $admissionBatchWeeks < $programmeWeeks) {
            return collect();
        }

        // Number of complete batches that fit
        $count = (int) floor($admissionBatchWeeks / $programmeWeeks);

        if ($count <= 0) {
            return collect();
        }

        $created = collect();
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
                ->where('centre_id', $centre->id)
                ->where('start_date', $currentStart->format('Y-m-d'))
                ->first();

            if (!$existing) {
                $batch = ProgrammeBatch::create([
                    'admission_batch_id' => $admissionBatch->id,
                    'programme_id' => $programme->id,
                    'centre_id' => $centre->id,
                    'start_date' => $currentStart,
                    'end_date' => $currentEnd,
                    'max_enrolments' => $centre->seat_count ?? 0,
                    'available_slots' => $centre->seat_count ?? 0,
                    'status' => true,
                ]);
                $created->push($batch);
            } else {
                $created->push($existing);
            }

            // Next batch starts the next school day after this one ends
            $currentStart = SchoolDayCalculator::add($currentEnd, 1);
        }

        return $created;
    }
}
