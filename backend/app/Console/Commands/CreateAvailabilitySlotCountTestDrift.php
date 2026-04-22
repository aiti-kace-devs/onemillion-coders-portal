<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CreateAvailabilitySlotCountTestDrift extends Command
{
    protected $signature = 'availability-slots:create-test-drift
        {--dry-run : Preview the selected test change without writing to the database}
        {--write : Actually create the test mismatch; without this, the command only previews}
        {--force : Allow the command outside local/development/testing environments; production is always blocked}
        {--centre-id= : Optional centre id to target}
        {--master-session-id= : Optional master session id to target}
        {--date= : Optional date for the test row, YYYY-MM-DD; defaults to today}
        {--delta=1 : Positive amount to add to displayed used seats}
        {--audit-after : Run the availability slot count check immediately after creating the mismatch}
        {--audit-repair-after-minutes=60 : Review-window minutes to pass to the immediate check}
        {--clear-cache : Clear application cache after creating the test mismatch}';

    protected $description = 'Development-only helper to create a safe test mismatch in displayed availability slot counts';

    public function handle(): int
    {
        $environment = app()->environment();
        if ($environment === 'production') {
            $this->error('Refusing to create test slot-count mismatch in production.');

            return self::FAILURE;
        }

        if (! app()->environment(['local', 'development', 'testing']) && ! $this->option('force')) {
            $this->error("Current environment is '{$environment}'. Re-run with --force only if this is a safe non-production database.");

            return self::FAILURE;
        }

        $connectionName = config('database.default');
        $connection = DB::connection($connectionName);

        try {
            $connection->getPdo();
        } catch (Throwable $e) {
            $this->error("Could not connect using database connection '{$connectionName}': {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->line("Environment: {$environment}");
        $this->line("Database connection: {$connectionName}");
        $this->line("Database name: {$connection->getDatabaseName()}");

        foreach (['daily_session_occupancy', 'centres', 'master_sessions'] as $table) {
            if (! Schema::connection($connectionName)->hasTable($table)) {
                $this->error("Required table '{$table}' does not exist on the current connection.");

                return self::FAILURE;
            }
        }

        $lock = Cache::lock('maintenance:test-availability-slot-drift', 300);
        if (! $lock->get()) {
            $this->error('Another test slot-count mismatch command is already running.');

            return self::FAILURE;
        }

        try {
            $date = $this->resolveDate();
            if (! $date) {
                return self::FAILURE;
            }

            $delta = min(1000, max(1, (int) $this->option('delta')));
            $existingRow = $this->findExistingOccupancyRow();
            $target = $existingRow ?: $this->buildNewOccupancyRowTarget($date);

            if (! $target) {
                return self::FAILURE;
            }

            $this->table(
                ['Field', 'Value'],
                [
                    ['Action', $existingRow ? 'Increase an existing displayed count' : 'Insert one test displayed-count row'],
                    ['Centre ID', $target->centre_id],
                    ['Master Session ID', $target->master_session_id],
                    ['Date', $target->date ?? $date],
                    ['Course Type', $target->course_type],
                    ['Delta', "+{$delta}"],
                ]
            );

            $dryRun = $this->option('dry-run') || ! $this->option('write');

            if ($dryRun) {
                $this->info('Dry run complete. No database rows were changed.');
                $this->line('To create the test mismatch, re-run with --write.');

                return self::SUCCESS;
            }

            $hasProtocolColumn = Schema::connection($connectionName)
                ->hasColumn('daily_session_occupancy', 'protocol_occupied_count');
            $hasCreatedAt = Schema::connection($connectionName)
                ->hasColumn('daily_session_occupancy', 'created_at');
            $hasUpdatedAt = Schema::connection($connectionName)
                ->hasColumn('daily_session_occupancy', 'updated_at');

            DB::transaction(function () use ($existingRow, $target, $date, $delta, $hasProtocolColumn, $hasCreatedAt, $hasUpdatedAt) {
                if ($existingRow) {
                    $values = [
                        'occupied_count' => max(0, (int) $existingRow->occupied_count) + $delta,
                    ];

                    if ($hasUpdatedAt) {
                        $values['updated_at'] = now();
                    }

                    DB::table('daily_session_occupancy')
                        ->where('id', $existingRow->id)
                        ->update($values);

                    return;
                }

                $values = [
                    'date' => $date,
                    'centre_id' => $target->centre_id,
                    'master_session_id' => $target->master_session_id,
                    'course_type' => $target->course_type,
                    'occupied_count' => $delta,
                ];

                if ($hasProtocolColumn) {
                    $values['protocol_occupied_count'] = 0;
                }

                if ($hasCreatedAt) {
                    $values['created_at'] = now();
                }

                if ($hasUpdatedAt) {
                    $values['updated_at'] = now();
                }

                DB::table('daily_session_occupancy')->insert($values);
            });

            if ($this->option('clear-cache')) {
                Artisan::call('cache:clear');
                $cacheOutput = trim(Artisan::output());
                if ($cacheOutput !== '') {
                    $this->line($cacheOutput);
                }
            }

            $this->info('Test mismatch created successfully. This changed only daily_session_occupancy, not bookings or admissions.');

            if ($this->option('audit-after')) {
                $this->line('Running the availability slot count check now so the admin alert can be created...');

                $exitCode = Artisan::call('occupancy:audit', [
                    '--limit' => 5,
                    '--repair-after-minutes' => max(0, (int) $this->option('audit-repair-after-minutes')),
                ]);

                $auditOutput = trim(Artisan::output());
                if ($auditOutput !== '') {
                    $this->line($auditOutput);
                }

                if ($exitCode !== self::SUCCESS) {
                    $this->error('The test mismatch was created, but the follow-up check did not complete successfully.');

                    return self::FAILURE;
                }

                $this->info('Follow-up check completed. Check Admin Dashboard or Admin Utilities for the alert.');
            } else {
                $this->line('Next: run php artisan occupancy:audit --limit=5 --repair-after-minutes=60');
            }

            $this->line('To repair: run php artisan occupancy:rebuild --force --clear-cache');

            return self::SUCCESS;
        } finally {
            optional($lock)->release();
        }
    }

    private function resolveDate(): ?string
    {
        $raw = $this->option('date');

        try {
            return $raw ? Carbon::parse($raw)->toDateString() : now()->toDateString();
        } catch (Throwable) {
            $this->error('Invalid --date value. Use YYYY-MM-DD.');

            return null;
        }
    }

    private function findExistingOccupancyRow()
    {
        $query = DB::table('daily_session_occupancy')->orderByDesc('id');

        if ($this->option('centre-id')) {
            $query->where('centre_id', (int) $this->option('centre-id'));
        }

        if ($this->option('master-session-id')) {
            $query->where('master_session_id', (int) $this->option('master-session-id'));
        }

        return $query->first();
    }

    private function buildNewOccupancyRowTarget(string $date): ?object
    {
        $centre = DB::table('centres')
            ->when($this->option('centre-id'), fn ($query) => $query->where('id', (int) $this->option('centre-id')))
            ->orderBy('id')
            ->first(['id']);

        if (! $centre) {
            $this->error('No matching centre found. Provide a valid --centre-id or seed/create a centre first.');

            return null;
        }

        $session = DB::table('master_sessions')
            ->when($this->option('master-session-id'), fn ($query) => $query->where('id', (int) $this->option('master-session-id')))
            ->orderByDesc('status')
            ->orderBy('id')
            ->first(['id', 'course_type']);

        if (! $session) {
            $this->error('No matching master session found. Provide a valid --master-session-id or seed/create a master session first.');

            return null;
        }

        return (object) [
            'centre_id' => (int) $centre->id,
            'master_session_id' => (int) $session->id,
            'date' => $date,
            'course_type' => (string) $session->course_type,
        ];
    }
}
