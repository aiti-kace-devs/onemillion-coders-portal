<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ContentToFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:content-to-files
                            {--pull : Export Statamic content from the database to files (share developed content)}
                            {--push : Import Statamic content from files into the database (inverse of push)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Statamic content between database and files. Use --push to export DB to files, --pull to import files to DB.';

    /**
     * Export commands (DB → files). Order respects dependencies.
     *
     * @var array<int, string>
     */
    protected array $exportCommands = [
        'statamic:eloquent:export-sites',
        'statamic:eloquent:export-blueprints',
        'statamic:eloquent:export-collections',
        'statamic:eloquent:export-entries',
        'statamic:eloquent:export-globals',
        'statamic:eloquent:export-taxonomies',
        'statamic:eloquent:export-navs',
        'statamic:eloquent:export-forms',
        'statamic:eloquent:export-assets',
        // 'statamic:eloquent:export-revisions',
    ];

    /**
     * Import commands (files → DB). Order matches SetupApplication where applicable.
     *
     * @var array<int, string>
     */
    protected array $importCommands = [
        // Sites must exist before other imports so Statamic can resolve the current site (e.g. Cascade).
        'statamic:eloquent:import-sites',
        'statamic:eloquent:import-assets',
        'statamic:eloquent:import-blueprints',
        'statamic:eloquent:import-collections',
        'statamic:eloquent:import-entries',
        'statamic:eloquent:import-forms',
        'statamic:eloquent:import-globals',
        'statamic:eloquent:import-taxonomies',
        'statamic:eloquent:import-navs',
        // 'statamic:eloquent:import-revisions',
        'statamic:eloquent:import-users',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $push = $this->option('push');
        $pull = $this->option('pull');

        if ($push && $pull) {
            $this->error('Use either --push or --pull, not both.');

            return self::FAILURE;
        }

        if (! $push && ! $pull) {
            $this->error('Specify --push (DB to files) or --pull (files to DB).');

            return self::FAILURE;
        }

        $commands = $pull ? $this->exportCommands : $this->importCommands;
        $direction = $pull ? 'Exporting database content to files' : 'Importing file content to database';

        $this->info($direction.'...');

        $phpBinary = defined('PHP_BINARY') ? PHP_BINARY : 'php';
        $artisan = base_path('artisan');

        foreach ($commands as $command) {
            $this->line('  Running: '.$command);
            $process = new Process(
                [$phpBinary, $artisan, $command, '--no-interaction'],
                base_path(),
                null,
                null,
                null
            );
            $process->run(function ($type, $buffer) {
                $this->output->write($buffer);
            });
            if (! $process->isSuccessful()) {
                $this->error('Command failed: '.$command);

                return self::FAILURE;
            }
        }

        $this->info($push ? 'Content exported to files.' : 'Content imported from files.');

        return self::SUCCESS;
    }
}
