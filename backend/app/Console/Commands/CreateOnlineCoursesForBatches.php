<?php

namespace App\Console\Commands;

use App\Models\Batch;
use App\Models\Centre;
use App\Models\Course;
use App\Models\Programme;
use Illuminate\Console\Command;

class CreateOnlineCoursesForBatches extends Command
{
    protected $signature = 'courses:create-online-for-batches';

    protected $description = 'Create missing courses for active online programmes across active centres in currently running admission batches';

    public function handle(): int
    {
        $currentYear = (int) now()->year;

        $runningBatches = Batch::query()
            ->where('year', $currentYear)
            ->where('status', true)
            ->where('completed', false)
            ->get(['id', 'title', 'start_date', 'end_date'])
            ->keyBy('id');

        if ($runningBatches->isEmpty()) {
            $this->warn('No running admission batch was found.');

            return self::SUCCESS;
        }

        $activeCentres = Centre::query()
            ->where('status', true)
            ->get(['id', 'title']);

        if ($activeCentres->isEmpty()) {
            $this->warn('No active centres were found.');

            return self::SUCCESS;
        }

        $programmes = Programme::query()
            ->where('status', true)
            ->whereRaw('LOWER(TRIM(mode_of_delivery)) = ?', ['online'])
            ->get();

        if ($programmes->isEmpty()) {
            $this->warn('No active online programmes were found in the programmes table.');

            return self::SUCCESS;
        }

        $createdCount = 0;
        $skippedCount = 0;
        $createdPerBatch = [];

        foreach ($runningBatches as $batch) {
            foreach ($programmes as $programme) {
                foreach ($activeCentres as $centre) {
                    $course = Course::firstOrCreate(
                        [
                            'centre_id' => $centre->id,
                            'programme_id' => $programme->id,
                            'batch_id' => $batch->id,
                        ],
                        [
                            // Generated automatically in Course::saving() using programme + centre titles.
                            'course_name' => null,
                            'duration' => $programme->duration,
                            'start_date' => $programme->start_date ?: $batch->start_date,
                            'end_date' => $programme->end_date ?: $batch->end_date,
                            'status' => true,
                        ]
                    );

                    if ($course->wasRecentlyCreated) {
                        $createdCount++;
                        $createdPerBatch[$batch->id] = ($createdPerBatch[$batch->id] ?? 0) + 1;
                    } else {
                        $skippedCount++;
                    }
                }
            }
        }

        $this->info("Created {$createdCount} course(s).");
        $this->line("Skipped {$skippedCount} existing course(s).");

        if (! empty($createdPerBatch)) {
            $this->newLine();
            $this->line('Created by admission batch:');

            foreach ($createdPerBatch as $batchId => $count) {
                $batch = $runningBatches->get($batchId);
                $batchLabel = $batch ? "{$batch->title} (#{$batch->id})" : "#{$batchId}";
                $this->line("- {$batchLabel}: {$count}");
            }
        }

        return self::SUCCESS;
    }
}
