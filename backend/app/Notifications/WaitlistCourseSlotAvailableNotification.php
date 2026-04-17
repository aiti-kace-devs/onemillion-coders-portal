<?php

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WaitlistCourseSlotAvailableNotification extends Notification
{
    use Queueable;

    public function __construct(public Course $course) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $this->course->loadMissing(['programme', 'centre']);

        $programmeTitle = $this->course->programme?->title ?? 'your selected programme';
        $centreTitle = $this->course->centre?->title ?? 'your selected centre';
        $regionTitle = $this->course->centre?->branch?->title ?? 'your selected region';
        $districtTitle = $this->course->centre?->districts?->first()?->title ?? 'your selected district';

        return (new MailMessage)
            ->subject('A slot is available for your waitlisted course')
            ->greeting("Hello {$notifiable->name},")
            ->line('A slot has opened up for a course you are waitlisted on.')
            ->line("**Region:** {$regionTitle}")
            ->line("**District:** {$districtTitle}")
            ->line("**Centre:** {$centreTitle}")
            ->line("**Programme:** {$programmeTitle}")
            ->line('Please visit your application status page to continue.')
            ->action('Book Your Slot Now', url('student/choose-course'))
            ->line('Slots are limited and will be filled on a first-come basis.')
            ->salutation('Best regards, One Million Coders Team');
    }
}
