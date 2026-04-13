<?php

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WaitlistSlotAvailableNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Course $course) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('A slot is now available for ' . $this->course->course_name)
            ->line('Good news! A slot has opened up for the course you are waiting for.')
            ->line('Course: ' . $this->course->course_name)
            ->line('Please log in to your portal to confirm your admission before the slot is taken.');
    }
}
