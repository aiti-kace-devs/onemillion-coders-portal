<?php

namespace App\Console\Commands;

use App\Models\MaintenanceAlert;
use App\Services\OccupancyReconciliationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AuditOccupancy extends Command
{
    protected $signature = 'occupancy:audit
        {--limit=20 : Number of drift samples to print}
        {--repair : Repair availability slot counts and clear cache when a mismatch is found}
        {--repair-after-minutes= : Create an admin-visible alert first, then auto-repair after this grace window}
        {--fail-on-drift : Return a failing exit code when drift is found}';

    protected $description = 'Check displayed availability slot counts against confirmed bookings and optionally repair mismatches';

    public function __construct(private readonly OccupancyReconciliationService $reconciliation)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $lock = Cache::lock('maintenance:occupancy-audit', 600);

        if (! $lock->get()) {
            $this->error('Another occupancy audit is already running.');

            return self::FAILURE;
        }

        try {
            $bookingCount = $this->reconciliation->confirmedCentreCapacityBookingCount();
            $this->info("Checking {$bookingCount} confirmed bookings against displayed availability slot counts...");

            $bar = $this->output->createProgressBar($bookingCount);
            $bar->start();

            $expectedRows = $this->reconciliation->expectedRows(fn () => $bar->advance());

            $bar->finish();
            $this->newLine();

            $actualRows = $this->reconciliation->actualRows();
            $diff = $this->reconciliation->diffRows(
                $expectedRows,
                $actualRows,
                max(1, (int) $this->option('limit'))
            );

            if ($diff['mismatch_count'] === 0) {
                $this->resolveVisibleDriftAlert('Availability slot count check passed. Displayed counts now match confirmed bookings.');
                $this->info('Availability slot count check passed. No mismatch found.');

                return self::SUCCESS;
            }

            $this->warn("Availability slot count check found {$diff['mismatch_count']} mismatched row(s).");

            $this->table(
                ['Issue', 'Centre', 'Date', 'Session', 'Course type', 'Course/cohort', 'Correct count', 'Displayed count'],
                array_map(function ($sample) {
                    $courseText = implode('; ', array_slice($sample['course_names'] ?? [], 0, 2));
                    $cohortText = implode('; ', array_slice($sample['cohort_names'] ?? [], 0, 2));

                    return [
                        $sample['issue'] ?? 'Availability slot count does not match confirmed bookings.',
                        $sample['centre_name'] ?? 'Unknown centre',
                        $sample['date'] ?? 'Unknown date',
                        $sample['session_name'] ?? 'Unknown session',
                        $sample['course_type'] ?? 'Unknown',
                        trim($courseText.' / '.$cohortText, ' /'),
                        $sample['correct_display'] ?? 'Unknown',
                        $sample['current_display'] ?? 'Unknown',
                    ];
                }, $diff['samples'])
            );

            Log::warning('Session seat count mismatch found', [
                'mismatch_count' => $diff['mismatch_count'],
                'samples' => $diff['samples'],
            ]);

            $alert = $this->recordDriftAlert($diff);

            if ($alert && $this->repairIsDeferred($alert)) {
                $this->warn("Automatic repair is deferred until {$alert->action_due_at->toDateTimeString()}.");

                return $this->option('fail-on-drift') ? self::FAILURE : self::SUCCESS;
            }

            if (
                $this->option('repair')
                || ($alert && $alert->status === MaintenanceAlert::STATUS_PENDING && $this->repairGraceMinutes() !== null)
            ) {
                return $this->repairDrift($diff['mismatch_count']);
            }

            return $this->option('fail-on-drift') ? self::FAILURE : self::SUCCESS;
        } finally {
            optional($lock)->release();
        }
    }

    private function recordDriftAlert(array $diff): ?MaintenanceAlert
    {
        if (! Schema::hasTable('maintenance_alerts')) {
            $this->warn('maintenance_alerts table is not available yet; drift was logged but no admin alert was stored.');

            return null;
        }

        $graceMinutes = $this->repairGraceMinutes();
        $existing = MaintenanceAlert::query()
            ->where('key', MaintenanceAlert::KEY_OCCUPANCY_DRIFT)
            ->visible()
            ->first();

        $detectedAt = $existing?->detected_at ?? now();
        $actionDueAt = $existing?->action_due_at;

        if ($actionDueAt === null && $graceMinutes !== null) {
            $actionDueAt = now()->addMinutes($graceMinutes);
        }

        $payload = [
            'mismatch_count' => $diff['mismatch_count'],
            'samples' => $diff['samples'],
            'last_audited_at' => now()->toDateTimeString(),
            'auto_repair_grace_minutes' => $graceMinutes,
        ];

        $status = in_array($existing?->status, [
            MaintenanceAlert::STATUS_REPAIRING,
            MaintenanceAlert::STATUS_FAILED,
        ], true)
            ? $existing->status
            : MaintenanceAlert::STATUS_PENDING;
        $isRepairing = $status === MaintenanceAlert::STATUS_REPAIRING;
        $message = match ($status) {
            MaintenanceAlert::STATUS_REPAIRING => 'Availability slot count repair is running now. Manual repair buttons are disabled until it finishes.',
            MaintenanceAlert::STATUS_FAILED => $existing?->message ?: 'The previous automatic availability slot count repair failed. Please review and run the safe repair manually.',
            default => 'Some availability slot counts shown to learners do not match confirmed bookings. You can run a safe repair now, or the system will repair it automatically after the review window.',
        };

        return MaintenanceAlert::updateOrCreate(
            ['key' => MaintenanceAlert::KEY_OCCUPANCY_DRIFT],
            [
                'type' => 'occupancy',
                'severity' => $isRepairing ? 'info' : ($status === MaintenanceAlert::STATUS_FAILED ? 'danger' : 'warning'),
                'status' => $status,
                'title' => 'Availability slot counts need attention',
                'message' => $message,
                'payload' => $payload,
                'detected_at' => $detectedAt,
                'action_due_at' => $actionDueAt,
                'resolved_at' => null,
                'resolved_by_admin_id' => null,
            ]
        );
    }

    private function repairIsDeferred(?MaintenanceAlert $alert): bool
    {
        if ($this->option('repair')) {
            return false;
        }

        if ($alert?->status !== MaintenanceAlert::STATUS_PENDING) {
            return false;
        }

        if ($this->repairGraceMinutes() === null || ! $alert?->action_due_at) {
            return false;
        }

        return now()->lt($alert->action_due_at);
    }

    private function repairGraceMinutes(): ?int
    {
        $option = $this->option('repair-after-minutes');

        if ($option === null || $option === '') {
            return null;
        }

        return max(0, (int) $option);
    }

    private function repairDrift(int $mismatchCount): int
    {
        $this->info('Repair option enabled. Rebuilding displayed availability slot counts now...');

        $exitCode = Artisan::call('occupancy:rebuild', [
            '--force' => true,
            '--clear-cache' => true,
        ]);

        $output = trim(Artisan::output());
        if ($output !== '') {
            $this->line($output);
        }

        if ($exitCode !== self::SUCCESS) {
            $this->markVisibleDriftAlertFailed('Automatic availability slot count repair failed. Please review and run the safe repair manually if needed.');
            $this->error('Availability slot count repair failed.');

            return self::FAILURE;
        }

        Log::info('Session seat count mismatch auto-repaired', [
            'mismatch_count' => $mismatchCount,
        ]);

        $this->info('Availability slot count repair complete.');

        return self::SUCCESS;
    }

    private function resolveVisibleDriftAlert(string $message): void
    {
        if (! Schema::hasTable('maintenance_alerts')) {
            return;
        }

        MaintenanceAlert::query()
            ->where('key', MaintenanceAlert::KEY_OCCUPANCY_DRIFT)
            ->visible()
            ->update([
                'status' => MaintenanceAlert::STATUS_RESOLVED,
                'message' => $message,
                'resolved_at' => now(),
            ]);
    }

    private function markVisibleDriftAlertFailed(string $message): void
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
