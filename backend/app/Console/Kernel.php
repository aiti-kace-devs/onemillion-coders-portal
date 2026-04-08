<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
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
        $schedule->command('partner:send-stale-progress-reminders')->hourly();
        $registry = $this->app->make(\App\Services\Partners\PartnerRegistry::class);
        foreach (array_keys($registry->all()) as $partnerCode) {
            $programSlug = \App\Support\PartnerProgramSettings::programSlugForPartner((string) $partnerCode);
            $schedule->command(sprintf(
                'partner:sync-program-progress %s %s --per-page=%d --updated-since=%s',
                $partnerCode,
                $programSlug,
                (int) config('services.partner_progress.bulk_per_page', 100),
                now()->subHours(2)->toIso8601String()
            ))
                ->hourly()
                ->withoutOverlapping(120);
        }
        $schedule->command('partner:monitor-sync-health')
            ->everyFifteenMinutes()
            ->withoutOverlapping();

        $schedule->command('partner:prune-history')
            ->dailyAt('02:30')
            ->withoutOverlapping();

        $schedule->command('horizon:snapshot')->everyFiveMinutes();

        // Purge expired / stale OTP verification records every minute
        $schedule->command('otp:clean')->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
