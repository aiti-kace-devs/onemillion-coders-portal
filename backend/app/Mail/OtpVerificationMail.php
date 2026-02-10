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
    public string $verificationUrl;
    public string $recipientEmail;
    public int $expiresInMinutes;
    public bool $hasPhone;

    /**
     * Create a new message instance.
     *
     * @param string $otpCode          The 6-digit OTP code (shown directly in the email)
     * @param string $verificationUrl  The signed verification link
     * @param string $recipientEmail   The user's email address
     * @param int    $expiresInMinutes How many minutes until the OTP expires
     * @param bool   $hasPhone         Whether a phone number is associated (changes messaging)
     */
    public function __construct(
        string $otpCode,
        string $verificationUrl,
        string $recipientEmail,
        int $expiresInMinutes = 10,
        bool $hasPhone = false
    ) {
        $this->otpCode = $otpCode;
        $this->verificationUrl = $verificationUrl;
        $this->recipientEmail = $recipientEmail;
        $this->expiresInMinutes = $expiresInMinutes;
        $this->hasPhone = $hasPhone;
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
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.otp-verification',
            with: [
                'otpCode'          => $this->otpCode,
                'verificationUrl'  => $this->verificationUrl,
                'recipientEmail'   => $this->recipientEmail,
                'expiresInMinutes' => $this->expiresInMinutes,
                'hasPhone'         => $this->hasPhone,
                'appName'          => config('app.name', 'One Million Coders'),
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
