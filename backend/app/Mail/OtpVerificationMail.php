<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpVerificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $otpCode;
    public string $recipientEmail;
    public int $expiresInMinutes;

    /**
     * Create a new message instance.
     *
     * @param string $otpCode          The 6-digit OTP code (shown directly in the email)
     * @param string $recipientEmail   The user's email address
     * @param int    $expiresInMinutes How many minutes until the OTP expires
     */
    public function __construct(
        string $otpCode,
        string $recipientEmail,
        int $expiresInMinutes = 10,
    ) {
        $this->otpCode = $otpCode;
        $this->recipientEmail = $recipientEmail;
        $this->expiresInMinutes = $expiresInMinutes;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Verification Code — ' . config('app.name', 'One Million Coders'),
        );
    }

    /**
     * Get the message content definition.
     *
     * Uses Laravel markdown mail components (x-mail::message) to ensure
     * consistent header, footer, branding and typography across ALL
     * emails the platform sends.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.otp-verification',
            with: [
                'otpCode'          => $this->otpCode,
                'recipientEmail'   => $this->recipientEmail,
                'expiresInMinutes' => $this->expiresInMinutes,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
