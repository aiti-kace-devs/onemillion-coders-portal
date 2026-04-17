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

        $reminders = [];

        if (config(ONE_WEEK_REMINDER, false)) {
            $reminders['1_week'] = Carbon::today()->addWeeks(1)->toDateString();
        }

        if (config(THREE_DAYS_REMINDER, false)) {
            $reminders['3_days'] = Carbon::today()->addDays(3)->toDateString();
        }

        if (config(ONE_DAY_REMINDER, false)) {
            $reminders['1_day'] = Carbon::today()->addDays(1)->toDateString();
        }

        if (empty($reminders)) {
            $this->info('No reminders configured.');
            return;
        }

        $totalSent = 0;

        foreach ($reminders as $type => $targetDate) {
            $this->info("Processing {$type} reminders for date {$targetDate}");

            Booking::with(['user', 'programmeBatch.programme', 'session', 'reminders'])
                ->where('status', true)
                ->whereHas('programmeBatch', function ($query) use ($targetDate) {
                    $query->whereDate('start_date', $targetDate);
                })
                ->chunkById(300, function ($bookings) use ($type, &$totalSent) {
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

                        $programmeName = $booking->session?->name ?? $booking->programmeBatch?->programme?->title ?? 'your chosen programme';
                        $sessionName = $booking->session?->session ?? 'your chosen session';
                        $startDate = $booking->programmeBatch?->start_date ? $booking->programmeBatch->start_date->format('l, jS F Y') : 'soon';
                        $centreName = $booking->centre?->title ?? 'your centre';

                        $variables = [
                            'first_name' => $user->first_name,
                            'name' => $user->name,
                            'programme' => $programmeName,
                            'session' => $sessionName,
                            'days' => $daysText,
                            'date' => $startDate,
                            'centre' => $centreName,
                        ];

                        if ($user->email) {
                            $subject = "Friendly Reminder: Your session starts in {$daysText}";
                            $emailSent = \App\Helpers\MailerHelper::sendTemplateEmail(
                                SESSION_REMINDER_EMAIL,
                                $user->email,
                                $variables,
                                $subject
                            );

                            if (!$emailSent) {
                                $user->notify(new SessionReminderNotification($booking, $daysText));
                            }
                        }

                        $phone = $user->mobile_no;
                        if ($phone) {
                            // Concise fallback < 160 chars
                            $defaultSms = "Hi {first_name}, your {programme} class at {centre} starts in {days} on {date}. See you!";

                            $smsMessage = \App\Helpers\SmsHelper::getTemplate(SESSION_REMINDER_SMS, $variables)
                                ?? \App\Helpers\SmsHelper::replaceVariables($defaultSms, $variables);

                            SendSmsJob::dispatch($phone, $smsMessage);
                        }

                        // Mark as sent
                        BookingReminder::create([
                            'booking_id' => $booking->id,
                            'type' => $type
                        ]);

                        $totalSent++;
                    }
                });
        }

        $this->info("Session reminders job completed. Sent {$totalSent} reminders.");
    }
}
