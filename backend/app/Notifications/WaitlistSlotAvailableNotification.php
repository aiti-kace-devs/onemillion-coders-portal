<?php

namespace App\Notifications;

use App\Models\ProgrammeBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WaitlistSlotAvailableNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ProgrammeBatch $programmeBatch,
        public int $courseId
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        // Eager-load relationships to avoid lazy-loading in queued notifications
        $this->programmeBatch->loadMissing(['programme', 'centre']);

        $programme = $this->programmeBatch->programme;
        $centre = $this->programmeBatch->centre;

        $programmeTitle = $programme ? $programme->title : 'the programme';
        $centreTitle = $centre ? $centre->title : 'a centre';
        $startDate = $this->programmeBatch->start_date->format('l, jS F Y');
        $endDate = $this->programmeBatch->end_date->format('l, jS F Y');

        return (new MailMessage)
            ->subject('A slot is now available for your waitlisted programme')
            ->greeting("Hello {$notifiable->name},")
            ->line("Great news! A slot has opened up for the programme you were waitlisted for.")
            ->line("**Programme:** {$programmeTitle}")
            ->line("**Centre:** {$centreTitle}")
            ->line("**Dates:** {$startDate} – {$endDate}")
            ->action('Book Your Slot Now', url("student/book-slot?batch_id={$this->programmeBatch->id}&course_id={$this->courseId}"))
            ->line('Slots are limited and will be filled on a first-come basis.')
            ->salutation('Best regards, One Million Coders Team');
    }
}
