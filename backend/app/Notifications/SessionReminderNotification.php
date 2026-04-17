<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Booking;

class SessionReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $booking;
    public $days;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking, string $days)
    {
        $this->booking = $booking;
        $this->days = $days;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $programmeName = $this->booking->courseSession?->name ?? $this->booking->programmeBatch?->programme?->title ?? 'your chosen programme';
        $sessionName = $this->booking->courseSession?->session ?? $this->booking->masterSession?->session ?? 'your chosen session';
        $startDate = $this->booking->programmeBatch?->start_date ? $this->booking->programmeBatch->start_date->format('l, jS F Y') : 'soon';

        return (new MailMessage)
                    ->subject("Friendly Reminder: Your session starts in {$this->days}")
                    ->greeting("Hello {$notifiable->name},")
                    ->line("This is a friendly reminder that your session for **{$programmeName}** starts in {$this->days}.")
                    ->line("**Session:** {$sessionName}")
                    ->line("**Start Date:** {$startDate}")
                    ->line('Please make sure to arrive on time and come prepared.')
                    ->line('We look forward to seeing you there!')
                    ->salutation('Best regards, One Million Coders Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'days' => $this->days,
        ];
    }
}
