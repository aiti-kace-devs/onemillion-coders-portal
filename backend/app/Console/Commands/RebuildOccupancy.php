<?php

namespace App\Console\Commands;

use App\Models\MaintenanceAlert;
use App\Services\OccupancyReconciliationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class RebuildOccupancy extends Command
{
    protected $signature = 'occupancy:rebuild
        {--dry-run : Show what would be rebuilt without changing daily_session_occupancy}
        {--force : Run in production/non-interactive environments without confirmation}
        {--clear-cache : Clear the application cache after a successful rebuild}';

    protected $description = 'Repair displayed availability slot counts from confirmed bookings';

    public function __construct(private readonly OccupancyReconciliationService $reconciliation)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (
            app()->environment('production')
            && ! $this->option('dry-run')
            && ! $this->option('force')
            && ! $this->confirm('This will recalculate the availability slot counts shown to learners. Continue?')
        ) {
            $this->warn('Availability slot count repair cancelled.');

            return self::FAILURE;
        }

        $lock = Cache::lock('maintenance:occupancy-rebuild', 600);

        if (! $lock->get()) {
            $this->error('Availability slot count repair is already running. Please wait for it to finish.');

            return self::FAILURE;
        }

        try {
            if (! $this->option('dry-run')) {
                $this->markSeatCountRepairRunning();
            }

            $bookingCount = $this->reconciliation->confirmedCentreCapacityBookingCount();
            $this->info("Processing {$bookingCount} confirmed bookings with session capacity...");

            $bar = $this->output->createProgressBar($bookingCount);
            $bar->start();

            $insertBuffer = $this->reconciliation->expectedRows(fn () => $bar->advance());

            $bar->finish();
            $this->newLine();

            $rowCount = count($insertBuffer);

            if ($this->option('dry-run')) {
                $this->info("Dry run complete. {$rowCount} availability slot count rows would be rebuilt.");

                return self::SUCCESS;
            }

            $chunks = array_chunk(array_values($insertBuffer), 500);
            $this->info("Rewriting displayed availability slot counts with {$rowCount} rows...");

            DB::transaction(function () use ($chunks) {
                DB::table('daily_session_occupancy')->delete();

                foreach ($chunks as $chunk) {
                    DB::table('daily_session_occupancy')->upsert(
                        $chunk,
                        ['date', 'centre_id', 'master_session_id'],
                        ['course_type', 'occupied_count', 'protocol_occupied_count']
                    );
                }
            });

            if ($this->option('clear-cache')) {
                $this->info('Clearing application cache...');
                Artisan::call('cache:clear');
                $cacheOutput = trim(Artisan::output());

                if ($cacheOutput !== '') {
                    $this->line($cacheOutput);
                }
            }

            $this->info('Availability slot count repair complete.');
            $this->markOccupancyDriftAlertRepaired();

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->markSeatCountRepairFailed('Availability slot count repair failed. Please review the command output and try again.');

            throw $e;
        } finally {
            optional($lock)->release();
        }
    }

    private function markSeatCountRepairRunning(): void
    {
        if (! Schema::hasTable('maintenance_alerts')) {
            return;
        }

        MaintenanceAlert::query()
            ->where('key', MaintenanceAlert::KEY_OCCUPANCY_DRIFT)
            ->visible()
            ->update([
                'status' => MaintenanceAlert::STATUS_REPAIRING,
                'severity' => 'info',
                'message' => 'Availability slot count repair is running now. Manual repair buttons are disabled until it finishes.',
            ]);
    }

    private function markOccupancyDriftAlertRepaired(): void
    {
        if (! Schema::hasTable('maintenance_alerts')) {
            return;
        }

        MaintenanceAlert::query()
            ->where('key', MaintenanceAlert::KEY_OCCUPANCY_DRIFT)
            ->visible()
            ->update([
                'status' => MaintenanceAlert::STATUS_REPAIRED,
                'severity' => 'info',
                'message' => 'Availability slot counts were repaired successfully from confirmed bookings.',
                'resolved_at' => now(),
            ]);
    }

    private function markSeatCountRepairFailed(string $message): void
    {
        if (! Schema::hasTable('maintenance_alerts')) {
            return;
        }

        MaintenanceAlert::query()
            ->where('key', MaintenanceAlert::KEY_OCCUPANCY_DRIFT)
            ->visible()
            ->update([
                'status' => MaintenanceAlert::STATUS_FAILED,
                'severity' => 'danger',
                'message' => $message,
            ]);
    }
}
