<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Helpers\MailerHelper;

class NotificationController extends Controller
{
    /**
     * Create a notification for a user.
     *
     * Usage:
     *   NotificationController::notify($user->id, 'EXAM_SUBMITTED', 'Exam Submitted', 'Your exam has been submitted.');
     *   NotificationController::notify($user->id, 'ADMISSION', 'Admission Update', $message, 'high');
     *   NotificationController::notify($user->id, 'email', 'Welcome', $rawEmailContent, 'normal', 'email');
     *   NotificationController::notify($user->id, 'campaign', 'Campaign Title', $message, 'normal', 'campaign', $campaign_id);
     */
    public static function notify(int $user_id, string $type, string $title, string $message, string $priority = 'normal', string $notification_type = null, int $campaign_id = null)
    {
        $cleanMessage = $notification_type === 'email'
            ? MailerHelper::convertToHtml($message)
            : $message;

        return Notification::create([
            'user_id' => $user_id,
            'campaign_id' => $campaign_id,
            'type' => $type,
            'title' => $title,
            'message' => $cleanMessage,
            'priority' => $priority,
            'read_at' => null,
        ]);
    }
}

