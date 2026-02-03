<?php

namespace App\Helpers;

use App\Mail\GenericEmail;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MailerHelper
{
    public static function sendTemplateEmail(
        string $templateName,
        string|array $emails,
        array $data,
        string $subject,
        bool $bulk = false
    ): bool {
        try {
            $content = self::getEmailTemplate($templateName, $data);

            if (!$content) {
                Log::warning("Email template not found: {$templateName}");
                return false;
            }

            $mailable = new GenericEmail($content, $subject);

            if ($bulk) {
                Mail::to(config('mail.from.address'))
                    ->bcc((array) $emails)
                    ->send($mailable);
            } else {
                Mail::to($emails)->send($mailable);
            }

            return true;

        } catch (\Throwable $e) {
            Log::error('MailerHelper failed', [
                'emails' => $emails,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private static function getEmailTemplate(string $name, array $data): ?string
    {
        $template = EmailTemplate::where('name', $name)->value('content');
        if (!$template) return null;

        foreach ($data as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }

        return $template;
    }
}
