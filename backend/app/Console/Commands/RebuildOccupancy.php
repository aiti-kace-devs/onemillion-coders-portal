<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildOccupancy extends Command
{
    protected $signature = 'occupancy:rebuild';

    protected $description = 'Truncate daily_session_occupancy and rebuild from all existing bookings';

    public function handle(): int
    {
        $this->info('Truncating daily_session_occupancy...');
        DB::table('daily_session_occupancy')->truncate();

        $bookings = Booking::with('programmeBatch')
            ->whereNotNull('master_session_id')
            ->whereNotNull('centre_id')
            ->whereNotNull('course_type')
            ->get();

        $this->info("Processing {$bookings->count()} bookings...");

        $bar = $this->output->createProgressBar($bookings->count());
        $bar->start();

        $insertBuffer = [];

        foreach ($bookings as $booking) {
            $batch = $booking->programmeBatch;
            if (!$batch || !$batch->start_date || !$batch->end_date) {
                $bar->advance();
                continue;
            }

            $period = CarbonPeriod::create($batch->start_date, $batch->end_date);

            foreach ($period as $date) {
                $key = "{$date->toDateString()}|{$booking->centre_id}|{$booking->master_session_id}";

                if (!isset($insertBuffer[$key])) {
                    $insertBuffer[$key] = [
                        'date' => $date->toDateString(),
                        'centre_id' => $booking->centre_id,
                        'master_session_id' => $booking->master_session_id,
                        'course_type' => $booking->course_type,
                        'occupied_count' => 0,
                    ];
                }

                $insertBuffer[$key]['occupied_count']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Bulk upsert in chunks
        $chunks = array_chunk(array_values($insertBuffer), 500);
        $this->info('Inserting ' . count($insertBuffer) . ' occupancy rows...');

        foreach ($chunks as $chunk) {
            DB::table('daily_session_occupancy')->upsert(
                $chunk,
                ['date', 'centre_id', 'master_session_id'],
                ['course_type', 'occupied_count']
            );
        }

        $this->info('✅ Occupancy rebuild complete.');

        return self::SUCCESS;
    }
}
