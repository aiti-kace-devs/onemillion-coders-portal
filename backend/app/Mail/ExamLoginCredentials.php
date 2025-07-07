<?php

namespace App\Mail;

use App\Events\UserRegistered;
use App\Models\User;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExamLoginCredentials extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    public $password, $deadline, $examUrl, $name, $email;



    public function __construct($std = null, $plainPassword = null, $deadline = null, $examUrl = null)
    {
        $this->name = $std?->name;
        $this->email = $std?->email;
        $this->password = $plainPassword;
        $this->deadline = $deadline;
        $this->examUrl = url('/student/exam');
    }


    /**
     * Build the message.
     *
     * @return $this
     */
    // public function build()
    // {

    //     return $this->subject('Your Exam Login Credentials')
    //         ->markdown('mail.exam_credentials')
    //         ->with([
    //             'name' => $this->std->name,
    //             'email' => $this->std->email,
    //             'password' => $this->plainPassword,
    //             'examUrl' => $examUrl,
    //             'deadline' => $this->deadline,
    //         ]);
    // }

    public function content()
    {

        return new Content(
            markdown: 'mail.exam_credentials'
        );
        // ->with([
        //     'name' => $this->std->name,
        //     'email' => $this->std->email,
        //     'password' => $this->plainPassword,
        //     'examUrl' => $this->examUrl,
        //     'deadline' => $this->deadline,
        // ]);
    }

    public function envelope()
    {
        return new Envelope(
            subject: 'Confirmation Successful',
        );
    }
}
