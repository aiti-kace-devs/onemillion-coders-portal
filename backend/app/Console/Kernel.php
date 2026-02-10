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

        $schedule->command('horizon:snapshot')->everyFiveMinutes();

        // Auto-admission: Check and run for scheduled courses daily at midnight
        $schedule->call(function () {
            $courses = \App\Models\Course::where('auto_admit_enabled', true)
                ->where('auto_admit_on', '<=', today())
                ->where(function($q) {
                    $q->whereNull('last_auto_admit_at')
                      ->orWhereDate('last_auto_admit_at', '<', today());
                })
                ->get();

            foreach ($courses as $course) {
                \Artisan::call('admission:auto-admit', ['course' => $course->id]);
                \Log::info("Scheduled admission run", ['course_id' => $course->id]);
            }
        })->daily()
          ->at('00:00')
          ->name('auto-admission')
          ->withoutOverlapping();

        // Refresh admission statistics cache daily
        $schedule->call(function () {
            \App\Models\Course::has('admissions')
                ->get()
                ->each(function($course) {
                    $batch = $course->batch ?? $course->batches()->latest()->first();
                    if ($batch) {
                        app(\App\Services\AdmissionStatisticsService::class)
                            ->refreshCache($course, $batch);
                    }
                });
        })->daily()->at('01:00');
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
