<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SetupApplication extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup-application
                            {--force : Force execution of commands regardless of version change}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check app version and run appropriate artisan commands if needed';

    protected string $currentVersion;
    protected ?string $previousVersion = null;
    protected string $versionFilePath;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->versionFilePath = storage_path('app/version.json');

        // Clear config first to ensure fresh version check
        $this->call('optimize:clear');

        // Get app version
        $this->currentVersion = config('app.version', '1.0.0');
        $this->previousVersion = $this->getStoredVersion() ?? Cache::get('app_previous_version');

        if ($this->checkVersionChange()) {
            $this->info('Version changed from ' . ($this->previousVersion ?? 'none') . ' to ' . $this->currentVersion);
            $this->info('Executed required commands.');
        } else {
            $this->info('No version change detected (current: ' . $this->currentVersion . ')');
        }

        // Cache config at the end
        $this->call('optimize');

        return 0;
    }

    protected function checkVersionChange(): bool
    {
        if ($this->previousVersion === null) {
            // First run, just store current version
            $this->storeCurrentVersion();
            return false;
        }

        if ($this->currentVersion !== $this->previousVersion) {
            $this->handleVersionChange();
            return true;
        }

        // Store current version to ensure it's always up to date
        $this->storeCurrentVersion();
        return false;
    }

    protected function handleVersionChange(): void
    {
        // Run version-specific commands
        $this->runVersionSpecificCommands();

        // Update stored version
        $this->storeCurrentVersion();
    }

    protected function runVersionSpecificCommands(): void
    {
        $commands = $this->getCommandsForVersion($this->currentVersion);

        foreach ($commands as $command) {
            if (is_array($command)) {
                // Handle command with parameters
                $commandName = $command[0];
                $parameters = $this->parseParameters($command[1] ?? '');
                $this->comment("Running command: {$commandName} with parameters: " . json_encode($parameters));
                $this->call($commandName, $parameters);
            } else {
                // Handle simple command
                $this->comment("Running command: {$command}");
                $this->call($command);
            }
        }
    }

    protected function parseParameters(string $paramString): array
    {
        $params = [];
        $parts = explode(',', $paramString);

        foreach ($parts as $part) {
            $part = trim($part);
            if (Str::startsWith($part, '--')) {
                // Handle flags (--force)
                $paramName = $part;
                $params[$paramName] = true;
            } elseif (Str::contains($part, '=')) {
                // Handle key=value pairs
                [$key, $value] = explode('=', $part, 2);
                $params[trim($key)] = trim($value);
            }
        }

        return $params;
    }

    protected function getCommandsForVersion(string $version): array
    {
        if (!isset($this->previousVersion)) {
            return [];
        }

        $current = explode('.', $version);
        $previous = explode('.', $this->previousVersion);

        // Major version change (1.x.x → 2.x.x)
        if ($current[0] !== $previous[0]) {
            return [
                ['migrate', '--force'],
                'basset:fresh',
                ['db:seed', '--force'],
                // 'optimize:clear',
                // 'optimize',
            ];
        }

        // Minor version change (1.1.x → 1.2.x)
        if ($current[1] !== $previous[1]) {
            return [
                ['migrate', '--force'],
                // 'optimize:clear',
                // 'optimize',
            ];
        }

        // Patch version change (1.1.1 → 1.1.2)
        // if ($current[2] !== $previous[2]) {
        //     return [
        //         'optimize:clear',
        //         'optimize',
        //     ];
        // }

        return [];
    }

    protected function storeCurrentVersion(): void
    {
        Cache::forever('app_previous_version', $this->currentVersion);
        $this->writeCurrentVersion($this->currentVersion);
    }

    protected function writeCurrentVersion(string $version): void
    {
        $data = [
            'version' => $version,
            'updated_at' => now()->toDateTimeString(),
            'created_at' => now()->toDateTimeString(),
        ];

        // Write to database
        DB::table('versions')->updateOrInsert(
            ['version' => $version],
            $data
        );
    }

    protected function getStoredVersion(): ?string
    {
        try {
            // Check if the migrations table exists
            if (!Schema::hasTable('migrations')) {
                $this->warn('Migrations table does not exist. Running migrations...');
                $this->call('migrate', ['--force' => true]);
            }

            // Try database first
            $lastVersion = DB::table('versions')->latest('updated_at')->value('version');

            return $lastVersion ?? '0.0.0';
        } catch (\Exception $e) {
            $this->error('Error retrieving stored version: ' . $e->getMessage());
            return null;
        }
    }
}
