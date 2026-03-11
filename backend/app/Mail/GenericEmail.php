<?php

namespace App\Mail;

use App\Helpers\MailerHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenericEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $deleteWhenMissingModels = true;
    public $markdownContent;
    public $subjectLine;
    /**
     * Create a new message instance.
     *
     * The raw markdown content is stored as-is. Do NOT pre-convert with
     * CommonMarkConverter here — the `markdown:` Content type causes Laravel's
     * <x-mail::message> component to process slot content through its own
     * markdown-to-HTML pipeline. Pre-converting would double-process the
     * content, corrupting headings, bold text, and HTML blocks.
     *
     * @return void
     */
    public function __construct($markdownContent = '', $subjectLine = '', $view = 'mail.generic-email', public $data = [])
    {
        $this->markdownContent = $markdownContent;
        $this->subjectLine = $subjectLine;
        $this->view = $view;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            markdown: $this->view,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }

    // Called when job succeeds
    public function success()
    {
        $this->removeTempView();
    }

    public function failed(?Throwable $exception): void
    {
        // Recreate the temp view so the queued job can be retried
        $file = str_replace('mail.temp.', '', $this->view);
        MailerHelper::createView($this->markdownContent, $file);
    }


    private function removeTempView()
    {
        if ($this->view !== 'mail.generic-email') {
            MailerHelper::removeView($this->view);
        }
    }
}
