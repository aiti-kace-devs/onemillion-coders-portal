<?php

namespace App\Helpers;

use App\Mail\GenericEmail;
use App\Models\EmailTemplate;
use App\Models\Notification;
use App\Models\User;
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

        $template = static::parseMarkdown($template);

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
            Mail::to(config('mail.from.address', 'no-reply@onemillioncoders.gov.gh'))
                ->bcc($emails)
                ->send($mailable);
        } else {
            Mail::to($emails)
                // ->bcc(config('mail.from.address', 'no-reply@gi-kace.gov.gh'))
                ->send($mailable);
        }

        activity('email')
            ->event('Sent email')
            ->log("Sent email: '{$subject}' to {$recipientLog}");
        static::createNotifications($emails, $subject, $content);
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

            self::sendGenericTemplateEmail($emails, $content, $subject, $bulk, $data);

            activity('email')
                ->event('Sent email template')
                ->log("Sent template email ({$templateName}): '{$subject}' to {$emails}");

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

    public static function createNotifications(string|array $emails, ?string $subject, string $message)
    {
        try {
            $emailList = is_array($emails) ? $emails : [$emails];
            $users = User::whereIn('email', $emailList)->get();

            $notifications = $users->map(function ($user) use ($subject, $message) {
                $personalizedMessage = static::replaceVariables($message, $user->toArray());
                $cleanMessage = static::convertToHtml($personalizedMessage);

                return [
                    'user_id' => $user->id,
                    'type' => 'email',
                    'title' => $subject ?? 'Notification',
                    'message' => $cleanMessage,
                    'priority' => 'normal',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            if (!empty($notifications)) {
                Notification::insert($notifications);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create notifications: ' . $e->getMessage());
        }
    }

    public static function convertToHtml(string $content): string
    {
        // Unwrap mail::message components to preserve inner content
        $content = preg_replace(
            "/@component\(\s*'mail::message'\s*\)\s*\n(.*?)\n\s*@endcomponent/s",
            '$1',
            $content
        );
        $content = preg_replace(
            "/\[component\]:\s?#\s?\('mail::message'\)\s*\n(.*?)\n\s*\[endcomponent\]:\s?#/s",
            '$1',
            $content
        );

        // Convert mail::button components to styled links (markdown-style)
        $content = preg_replace(
            "/\[component\]:\s?#\s?\('mail::button',\s*\['url'\s*=>\s*'([^']+)'\]\)\s*\n(.*?)\n\s*\[endcomponent\]:\s?#/s",
            '<p><a href="$1" style="display:inline-block;padding:10px 20px;background:#f9a825;color:#000;font-weight:bold;border-radius:12px;text-decoration:none;">$2</a></p>',
            $content
        );

        // Convert mail::button components to styled links (@component style)
        $content = preg_replace(
            "/@component\(\s*'mail::button'\s*,\s*\['url'\s*=>\s*'([^']+)'\]\s*\)\s*\n(.*?)\n\s*@endcomponent/s",
            '<p><a href="$1" style="display:inline-block;padding:10px 20px;background:#f9a825;color:#000;font-weight:bold;border-radius:12px;text-decoration:none;">$2</a></p>',
            $content
        );

        // Convert mail::panel components to styled divs (markdown-style)
        $content = preg_replace(
            "/\[component\]:\s?#\s?\('mail::panel'\)\s*\n(.*?)\n\s*\[endcomponent\]:\s?#/s",
            '<div style="padding:12px 16px;background:#f3f4f6;border-left: 4px solid #f9a825;border-radius:8px;margin:8px 0;">$1</div>',
            $content
        );

        // Convert mail::panel components to styled divs (@component style)
        $content = preg_replace(
            "/@component\(\s*'mail::panel'\s*\)\s*\n(.*?)\n\s*@endcomponent/s",
            '<div style="padding:12px 16px;background:#f3f4f6;border-left: 4px solid #f9a825;border-radius:8px;margin:8px 0;">$1</div>',
            $content
        );

        // Remove any remaining component/endcomponent lines
        $content = preg_replace('/\[(end)?component\]:\s?#\s?(\(.*?\))?\s*/i', '', $content);
        $content = preg_replace('/@component\([^\)]*\)\s*/i', '', $content);
        $content = preg_replace('/@endcomponent\s*/i', '', $content);

        // Convert ## headings to <h2>
        $content = preg_replace('/^##\s*(.+)$/m', '<h2 style="margin:0 0 8px;">$1</h2>', $content);

        // Convert **bold** to <strong>
        $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);

        // Convert markdown links [text](url) to <a>
        $content = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2" style="color:#3869d4;font-weight:bold;">$1</a>', $content);

        // Convert <br> and line breaks
        $content = str_replace('<br>', '<br/>', $content);

        // Convert double newlines to paragraph breaks
        $content = preg_replace('/\n{2,}/', '</p><p>', $content);

        // Convert single newlines to <br/>
        $content = preg_replace('/\n/', '<br/>', $content);

        // Wrap in paragraph
        $content = '<p>' . $content . '</p>';

        // Unwrap block elements that ended up inside a paragraph
        $content = preg_replace('/<p>\s*(<(div|h[1-6]|ul|ol)[^>]*>)/i', '$1', $content);
        $content = preg_replace('/(<\/(div|h[1-6]|ul|ol)>)\s*<\/p>/i', '$1', $content);

        // Clean up empty paragraphs
        $content = preg_replace('/<p>\s*<\/p>/', '', $content);

        return trim($content);
    }
}
