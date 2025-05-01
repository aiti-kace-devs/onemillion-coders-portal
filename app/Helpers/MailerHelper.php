<?php

namespace App\Helpers;

use App\Mail\GenericEmail;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Qoraiche\MailEclipse\MailEclipse;

class MailerHelper
{
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

    public static function getEmailTemplate(string $templateName, array $data)
    {
        $template = EmailTemplate::where('name', $templateName)->select('content')->first();
        if (!$template) {
            return;
        }

        // replace variables
        return  static::replaceVariables($template->content, $data);
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

    public static function sendGenericTemplateEmail(string|array $emails, string $content, $subject = null, $bulk = false)
    {
        $replaceContent = MailEclipse::markdownedTemplateToView(false, $content);
        $filename = static::createView($replaceContent);
        if (!$filename) {
            Log::error('Unable to send bulk image, view not created');
            return;
        }
        $mailable =  new GenericEmail($replaceContent, $subject, "mail.temp.$filename");

        if ($bulk) {
            Mail::to(config('mail.from.address', 'no-reply@gi-kace.gov.gh'))
                ->bcc($emails)
                ->send($mailable);
        } else {
            Mail::to($emails)
                ->bcc(config('mail.from.address', 'no-reply@gi-kace.gov.gh'))
                ->send($mailable);
        }
    }


    public static function sendTemplateEmail(string $templateName, string|array $emails, array $data, $subject = null, $bulk = false)
    {
        $content = static::getEmailTemplate($templateName, $data);
        if (!$content) {
            return;
        }

        static::sendGenericTemplateEmail($emails, $content, $subject, $bulk);
    }

    private static function createView($content)
    {
        $filename = time() . '_' . Str::random(5);
        if (!is_dir(resource_path("views/mail/temp"))) {
            mkdir(resource_path("views/mail/temp"));
        }
        $jobViewFilePath = resource_path("views/mail/temp/$filename.blade.php");
        $result = file_put_contents($jobViewFilePath, "<x-mail::message>$content   <br>   Thanks,   {{ config('app.name') }}</x-mail::message>");
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
