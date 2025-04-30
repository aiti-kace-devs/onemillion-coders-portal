<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use League\CommonMark\CommonMarkConverter;

class GenericEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $markdownContent;
    public $subjectLine;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($markdownContent = '', $subjectLine = '', $view = 'mail.generic-email', public $data = [])
    {
        $this->markdownContent = $markdownContent;
        $this->subjectLine = $subjectLine;
        $this->view = $view;

        $converter = new CommonMarkConverter();
        $this->markdownContent = $converter->convert($markdownContent)->getContent();
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

    // public function build()
    // {
    //     $combinedMarkdown = "\n\n" . $this->markdownContent; // Concatenate with newlines for separation

    //     $converter = new CommonMarkConverter();
    //     $htmlContent = $converter->convert($combinedMarkdown)->getContent();

    //     return $this->subject($this->subjectLine)
    //         ->html($htmlContent);
    // }
}
