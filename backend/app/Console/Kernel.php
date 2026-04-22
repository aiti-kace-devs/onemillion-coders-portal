<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        // $schedule->command('queue:retry all')
        // ->everySixHours();

        // $schedule->command('queue:work', ['--sleep=3', '--tries=3', '--max-time=300'])
        //     ->everyFiveMinutes();

        $schedule->command('queue:prune-failed', ['--hours=24'])
            ->dailyAt('01:00');

        $schedule->command('email:sendFeedback')->everyTenMinutes();

        $schedule->command('horizon:snapshot')->everyFiveMinutes();

        // Purge expired / stale OTP verification records every minute
        $schedule->command('otp:clean')->everyMinute();

        $schedule->command('metrics:sync-public-statistics')->dailyAt('02:00');

        $schedule->command('occupancy:audit', [
            '--limit' => 10,
            '--repair-after-minutes' => config('utilities.occupancy_alert.auto_repair_grace_minutes', 60),
        ])
            ->dailyAt('02:30')
            ->withoutOverlapping();

        $schedule->command('occupancy:repair-due')
            ->everyTenMinutes()
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
