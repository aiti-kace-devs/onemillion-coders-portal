<?php

namespace App\Jobs;

use App\Helpers\MailerHelper;
use App\Mail\GenericEmail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Qoraiche\MailEclipse\Facades\MailEclipse;

class SendBulkEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $student_ids;
    public $subject;
    public $message;
    public $template;
    public $list;
    public $correctTemplate = false;

    public $jobViewFilePath = '';
    private $filename = '';


    /**
     * Create a new job instance.
     */
    public function __construct(public array $data)
    {
        $this->student_ids = $data['student_ids'] ?? null;
        $this->subject = $data['subject'];
        $this->message = $data['message'] ?? null;
        $this->template = $data['template'] ?? null;
        $this->correctTemplate = false;
        $this->list = $data['list'] ?? null;

        if (
            $this->template &&
            class_exists($this->template) &&
            is_subclass_of($this->template, \Illuminate\Mail\Mailable::class)
        ) {
            $this->correctTemplate = true;
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Determine whether we need to send one message or multiple
        $messageContainsCurlyBrackets = isset($this->message) ? Str::containsAll($this->message, ['{', '}']) : false;

        // If list_name is provided, fetch emails from the database view
        if ($this->list) {
            $this->handleListEmail();
            return;
        }

        // Original logic for student_ids
        if ($this->student_ids) {
            collect($this->student_ids)->chunk(200)->each(function ($ids) use ($messageContainsCurlyBrackets) {
                $this->processChunk($ids, $messageContainsCurlyBrackets);
            });
        }
    }

    /**
     * Handle email sending for list_name case
     */
    protected function handleListEmail(): void
    {
        // Fetch data from the database view
        $recipients = DB::table($this->list)->get();
        // Process in chunks to avoid memory issues
        $recipients->chunk(200)->each(function ($chunk) {
            if (isset($this->message)) {
                // Check if message contains variables
                $messageContainsVariables = Str::containsAll($this->message, ['{', '}']);

                if ($messageContainsVariables) {
                    foreach ($chunk as $recipient) {
                        $message = MailerHelper::replaceVariables($this->message, (array)$recipient);
                        MailerHelper::sendGenericTemplateEmail($recipient->email, $message, $this->subject);
                    }
                } else {
                    $emails = $chunk->pluck('email')->all();
                    MailerHelper::sendGenericTemplateEmail($emails, $this->message, $this->subject, true);
                }
            } else if ($this->template && $this->correctTemplate) {
                foreach ($chunk as $recipient) {
                    Mail::to($recipient->email)
                        ->bcc(config('mail.from.address', 'no-reply@gi-kace.gov.gh'))
                        ->send(new $this->template($recipient));
                }
            }
        });
    }

    /**
     * Process a chunk of student IDs
     */
    protected function processChunk($ids, $messageContainsCurlyBrackets): void
    {
        if (isset($this->message)) {
            if ($messageContainsCurlyBrackets) {
                $chunkedUsers = User::whereIn('id', $ids)->get()->all();
                foreach ($chunkedUsers as $user) {
                    $message = MailerHelper::replaceVariables($this->message, $user->toArray());
                    MailerHelper::sendGenericTemplateEmail($user->email, $message, $this->subject);
                }
            } else {
                $emails = User::whereIn('id', $ids)->select('email')->pluck('email')->all();
                MailerHelper::sendGenericTemplateEmail($emails, $this->message, $this->subject, true);
            }
        } else if ($this->template && $this->correctTemplate) {
            $chunkedUsers = User::whereIn('id', $ids)->get()->all();
            foreach ($chunkedUsers as $user) {
                Mail::to($user->email)
                    ->bcc(config('mail.from.address', 'no-reply@gi-kace.gov.gh'))
                    ->send(new $this->template($user));
            }
        }
    }

    private function getGenericTemplateEmail(string $content, $subject = null)
    {
        $replaceContent = MailEclipse::markdownedTemplateToView(false, $content);
        $this->createView($replaceContent);
        return new GenericEmail($replaceContent, $subject, 'email.' . $this->filename);
    }

    private function sendBulkGenericTemplateEmail(string|array $emails, string $content, $subject = null, $bulk = false)
    {
        $replaceContent = MailEclipse::markdownedTemplateToView(false, $content);
        $filename = $this->createView($replaceContent);
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
        $this->removeView($filename);
    }


    private function createView($content)
    {
        $filename = time() . '_' . Str::random(5);
        if (!is_dir(resource_path("views/mail/temp"))) {
            mkdir(resource_path("views/mail/temp"));
        }
        $jobViewFilePath = resource_path("views/mail/temp/$filename.blade.php");
        $result = file_put_contents($jobViewFilePath, "<x-mail::message>$content</x-mail::message>");
        if (!$result) {
            return false;
        }
        return $filename;
    }

    private function removeView(string $filename)
    {
        $jobViewFilePath = resource_path("views/mail/temp/$filename.blade.php");
        if (file_exists($jobViewFilePath)) {
            unlink($jobViewFilePath);
        }
    }

    private function getView()
    {
        if (Storage::exists($this->jobViewFilePath)) {
            return Storage::get($this->jobViewFilePath);
        } else {
            return null;
        }
    }
}
