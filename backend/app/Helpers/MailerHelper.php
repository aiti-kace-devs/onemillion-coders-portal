<?php

namespace App\Helpers;

use App\Mail\GenericEmail;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MailerHelper
{
    /**
     * Replicate MailEclipse's markdown to blade conversion logic.
     * Converts [component]: # into @component
     */
    public static function parseMarkdown($content)
    {
        $components = ['component', 'endcomponent', 'slot', 'endslot', 'include', 'if', 'else', 'endif', 'foreach', 'endforeach'];

        foreach ($components as $component) {
            $pattern = "/\[{$component}]:\s?#\s?/i";
            $content = preg_replace($pattern, "@{$component}", $content);
        }

        return $content;
    }

    public static function getMailableClasses()
    {
        $mailables = [];
        $files = File::allFiles(app_path('Mail'));

        foreach ($files as $file) {
            $namespace = 'App\\Mail\\';
            $className = $namespace . str_replace(['/', '.php'], ['\\', ''], File::name($file->getRelativePathname()));

            if (class_exists($className) && is_subclass_of($className, \Illuminate\Mail\Mailable::class)) {
                $mailables[] = $className;
            }
        }

        return $mailables;
    }

    private static function getEmailTemplate(string $name, array $data): ?string
    {
        $template = EmailTemplate::where('name', $name)->value('content');
        if (!$template)
            return null;

        foreach ($data as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }

        return $template;
    }

    public static function replaceVariables($content, $data)
    {
        foreach ($data as $key => $value) {
            if (Str::contains($content, $key))
                $content = str_replace('{' . $key . '}', $value, $content);
            if (Str::contains($content, $key))
                $content = str_replace('{' . $key . '}', $value, $content);
        }

        return $content;
    }

    public static function sendGenericTemplateEmail(string|array $emails, string $content, $subject = null, $bulk = false, $data = [])
    {
        $replaceContent = static::parseMarkdown($content);
        $filename = static::createView($replaceContent);
        if (!$filename) {
            Log::error('Unable to send bulk image, view not created');
            return;
        }
        $mailable = new GenericEmail($replaceContent, $subject, "mail.temp.$filename", $data);

        $recipientCount = is_array($emails) ? count($emails) : 1;
        $recipientLog = $bulk ? "{$recipientCount} recipients (BCC)" : (is_array($emails) ? implode(', ', $emails) : $emails);

        if ($bulk) {
            Mail::to(config('mail.from.address', 'no-reply@gi-kace.gov.gh'))
                ->bcc($emails)
                ->send($mailable);
        } else {
            Mail::to($emails)
                ->bcc(config('mail.from.address', 'no-reply@gi-kace.gov.gh'))
                ->send($mailable);
        }

        activity()->log("Sent email: '{$subject}' to {$recipientLog}");
    }


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

            $recipientCount = is_array($emails) ? count($emails) : 1;
            $recipientLog = $bulk ? "{$recipientCount} recipients (BCC)" : (is_array($emails) ? implode(', ', $emails) : $emails);

            if ($bulk) {
                Mail::to(config('mail.from.address'))
                    ->bcc((array) $emails)
                    ->send($mailable);
            } else {
                Mail::to($emails)->send($mailable);
            }

            activity()->log("Sent template email ({$templateName}): '{$subject}' to {$recipientLog}");

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

    public static function createView($content, $filename = null)
    {
        $filename = $filename ?? time() . '_' . Str::random(5);
        if (!is_dir(resource_path("views/mail/temp"))) {
            mkdir(resource_path("views/mail/temp"));
        }
        $jobViewFilePath = resource_path("views/mail/temp/$filename.blade.php");
        $converted = html_entity_decode($content);
        $result = file_put_contents($jobViewFilePath, "<x-mail::message>$converted   <br>   Thanks,   {{ config('app.name') }}</x-mail::message>");
        if (!$result) {
            return false;
        }
        return $filename;
    }

    public static function removeView(string $filename)
    {
        $file = str_replace('mail.temp.', '', $filename);
        $jobViewFilePath = resource_path("views/mail/temp/$file.blade.php");
        if (file_exists($jobViewFilePath)) {
            unlink($jobViewFilePath);
        }
    }
}
