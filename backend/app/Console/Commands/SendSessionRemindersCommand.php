<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\BookingReminder;
use App\Notifications\SessionReminderNotification;
use App\Jobs\SendSmsJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendSessionRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-session-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send 1-week, 3-day, and 1-day reminders to students with upcoming sessions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting session reminders job...');

        $reminders = [
            '1_week' => Carbon::today()->addWeeks(1)->toDateString(),
            '3_days' => Carbon::today()->addDays(3)->toDateString(),
            '1_day' => Carbon::today()->addDays(1)->toDateString(),
        ];

        $totalSent = 0;

        foreach ($reminders as $type => $targetDate) {
            $this->info("Processing {$type} reminders for date {$targetDate}");

            $bookings = Booking::with(['user', 'programmeBatch.programme', 'courseSession', 'masterSession', 'reminders'])
                ->where('status', true)
                ->whereHas('programmeBatch', function ($query) use ($targetDate) {
                    $query->whereDate('start_date', $targetDate);
                })
                ->get();

            /** @var \App\Models\Booking $booking */
            foreach ($bookings as $booking) {
                if ($booking->reminders->contains('type', $type)) {
                    continue;
                }

                $user = $booking->user;

                if (!$user) {
                    continue;
                }

                $daysText = str_replace('_', ' ', $type);
                $daysText = str_replace('days', 'days', $daysText);

                if ($user->email) {
                    $user->notify(new SessionReminderNotification($booking, $daysText));
                }

                $phone = $user->mobile_no;
                if ($phone) {
                    $programmeName = $booking->courseSession?->name ?? $booking->programmeBatch?->programme?->title ?? 'your chosen programme';
                    $sessionName = $booking->courseSession?->session ?? $booking->masterSession?->session ?? 'your chosen session';
                    $startDate = $booking->programmeBatch?->start_date ? $booking->programmeBatch->start_date->format('l, jS F Y') : 'soon';

                    $smsMessage = "Hi {$user->first_name}, friendly reminder that your {$programmeName} ({$sessionName}) session starts in {$daysText} on {$startDate}. See you soon!";

                    SendSmsJob::dispatch($phone, $smsMessage);
                }

                // 3. Mark as sent
                BookingReminder::create([
                    'booking_id' => $booking->id,
                    'type' => $type
                ]);

                $totalSent++;
            }
        }

        $this->info("Session reminders job completed. Sent {$totalSent} reminders.");
    }
}
