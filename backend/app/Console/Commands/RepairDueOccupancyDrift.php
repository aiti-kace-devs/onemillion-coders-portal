<?php

namespace App\Console\Commands;

use App\Models\MaintenanceAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class RepairDueOccupancyDrift extends Command
{
    protected $signature = 'occupancy:repair-due {--force : Repair even when the alert due time has not passed}';

    protected $description = 'Run safe availability slot count repair only when an alert has passed its admin review window';

    public function handle(): int
    {
        if (! Schema::hasTable('maintenance_alerts')) {
            $this->warn('maintenance_alerts table is not available yet.');

            return self::SUCCESS;
        }

        $alert = MaintenanceAlert::query()
            ->where('key', MaintenanceAlert::KEY_OCCUPANCY_DRIFT)
            ->where('status', MaintenanceAlert::STATUS_PENDING)
            ->first();

        if (! $alert) {
            $this->info('No active availability slot count alert.');

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            if (! $alert->action_due_at) {
                $this->info('Active availability slot count alert has no automatic repair due time.');

                return self::SUCCESS;
            }

            if (now()->lt($alert->action_due_at)) {
                $this->info("Availability slot count auto-repair is not due until {$alert->action_due_at->toDateTimeString()}.");

                return self::SUCCESS;
            }
        }

        $lock = Cache::lock('maintenance:occupancy-auto-repair', 600);

        if (! $lock->get()) {
            $this->error('Another availability slot count repair is already running.');

            return self::FAILURE;
        }

        try {
            $updated = MaintenanceAlert::query()
                ->whereKey($alert->id)
                ->where('status', MaintenanceAlert::STATUS_PENDING)
                ->update([
                    'status' => MaintenanceAlert::STATUS_REPAIRING,
                    'severity' => 'info',
                    'message' => 'The review window has ended. Automatic availability slot count repair is running now.',
                ]);

            if ($updated !== 1) {
                $this->info('Availability slot count alert is already being handled.');

                return self::SUCCESS;
            }

            $this->info('Repair window elapsed. Running safe availability slot count repair...');

            $exitCode = Artisan::call('occupancy:rebuild', [
                '--force' => true,
                '--clear-cache' => true,
            ]);

            $output = trim(Artisan::output());
            if ($output !== '') {
                $this->line($output);
            }

            if ($exitCode !== self::SUCCESS) {
                $alert->update([
                    'status' => MaintenanceAlert::STATUS_FAILED,
                    'severity' => 'danger',
                    'message' => 'Automatic availability slot count repair failed. Please review the command output and run a manual repair if needed.',
                ]);

            Log::error('Availability slot count auto-repair failed', [
                    'alert_id' => $alert->id,
                    'exit_code' => $exitCode,
                ]);

                return self::FAILURE;
            }

            Log::info('Availability slot count mismatch repaired after admin grace window', [
                'alert_id' => $alert->id,
            ]);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $alert->update([
                'status' => MaintenanceAlert::STATUS_FAILED,
                'severity' => 'danger',
                'message' => 'Automatic availability slot count repair failed. Please review the command output and run a manual repair if needed.',
            ]);

            Log::error('Availability slot count auto-repair crashed', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            optional($lock)->release();
        }
    }
}
