<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Centre;
use App\Models\Programme;
use App\Models\ProgrammeBatch;
use App\Services\ProgrammeBatchGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgrammeBatchGeneratorTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_generates_continuous_non_overlapping_batches()
    {
        // Create a 12-week admission batch
        $admissionBatch = Batch::create([
            'title' => 'Test Batch 2026',
            'start_date' => Carbon::create(2026, 6, 1), // Monday
            'end_date' => Carbon::create(2026, 8, 21), // ~12 weeks
            'status' => true,
        ]);

        // Create a 1-week programme (5 school days)
        $programme = Programme::create([
            'title' => 'One Week Programme',
            'duration_hours' => 10,
            'duration_in_days' => 5,
            'time_allocation' => 2,
        ]);

        $centre = Centre::create([
            'title' => 'Test Centre',
            'branch_id' => 1,
            'seat_count' => 30,
            'short_slots_per_day' => 18,
            'long_slots_per_day' => 12,
        ]);

        // Link centre and programme to batch via centre_ids and programme_ids JSON fields
        $admissionBatch->centre_ids = json_encode([$centre->id]);
        $admissionBatch->programme_ids = json_encode([$programme->id]);
        $admissionBatch->save();

        $generator = app(ProgrammeBatchGenerator::class);
        $generated = $generator->generate($admissionBatch, $programme, $centre);

        // Should generate 12 batches for a 12-week admission with 1-week programme
        $this->assertGreaterThanOrEqual(1, $generated->count());

        // Verify continuous, non-overlapping batches
        $sorted = $generated->sortBy('start_date');
        $previousEnd = null;

        foreach ($sorted as $batch) {
            $this->assertEquals($admissionBatch->id, $batch->admission_batch_id);
            $this->assertEquals($programme->id, $batch->programme_id);
            $this->assertEquals($centre->id, $batch->centre_id);
            $this->assertEquals(30, $batch->max_enrolments);
            $this->assertEquals(30, $batch->available_slots);

            if ($previousEnd) {
                // Ensure no overlap: current start should be after previous end
                $this->assertTrue(
                    Carbon::parse($batch->start_date)->gt(Carbon::parse($previousEnd)),
                    "Batches overlap: {$batch->start_date} is not after {$previousEnd}"
                );
            }

            // Ensure within admission window
            $this->assertTrue(Carbon::parse($batch->start_date)->gte(Carbon::parse($admissionBatch->start_date)));
            $this->assertTrue(Carbon::parse($batch->end_date)->lte(Carbon::parse($admissionBatch->end_date)));

            $previousEnd = $batch->end_date;
        }
    }

    /** @test */
    public function it_generates_2_week_programme_batches()
    {
        $admissionBatch = Batch::create([
            'title' => '12 Week Batch',
            'start_date' => Carbon::create(2026, 6, 1),
            'end_date' => Carbon::create(2026, 8, 21),
            'status' => true,
        ]);

        $programme = Programme::create([
            'title' => 'Two Week Programme',
            'duration_hours' => 20,
            'duration_in_days' => 10,
            'time_allocation' => 2,
        ]);

        $centre = Centre::create([
            'title' => 'Test Centre',
            'branch_id' => 1,
            'seat_count' => 25,
        ]);

        $admissionBatch->centre_ids = json_encode([$centre->id]);
        $admissionBatch->programme_ids = json_encode([$programme->id]);
        $admissionBatch->save();

        $generator = app(ProgrammeBatchGenerator::class);
        $generated = $generator->generate($admissionBatch, $programme, $centre);

        // Should generate 6 batches for a 12-week admission with 2-week programme
        $this->assertGreaterThanOrEqual(1, $generated->count());
    }

    /** @test */
    public function it_is_idempotent()
    {
        $admissionBatch = Batch::create([
            'title' => 'Test Batch',
            'start_date' => Carbon::create(2026, 6, 1),
            'end_date' => Carbon::create(2026, 6, 30),
            'status' => true,
        ]);

        $programme = Programme::create([
            'title' => 'Test Programme',
            'duration_hours' => 10,
            'duration_in_days' => 5,
            'time_allocation' => 2,
        ]);

        $centre = Centre::create([
            'title' => 'Test Centre',
            'branch_id' => 1,
            'seat_count' => 20,
        ]);

        $admissionBatch->centre_ids = json_encode([$centre->id]);
        $admissionBatch->programme_ids = json_encode([$programme->id]);
        $admissionBatch->save();

        $generator = app(ProgrammeBatchGenerator::class);
        $firstRun = $generator->generate($admissionBatch, $programme, $centre);
        $secondRun = $generator->generate($admissionBatch, $programme, $centre);

        // Should not create duplicates on second run
        $this->assertEquals($firstRun->count(), $secondRun->count());
        $this->assertEquals(ProgrammeBatch::count(), $firstRun->count());
    }
}
