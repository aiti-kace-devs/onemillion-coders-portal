<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RenderedTemplateEmail extends Mailable
{
    public function __construct(
        public string $subjectLine,
        public string $viewName
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: $this->viewName,
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
