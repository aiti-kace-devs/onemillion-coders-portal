<?php

namespace App\Mail;

use App\Models\Centre;
use App\Models\Course;
use App\Models\Programme;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentAdmitted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $student = null;
    public $course;
    public $centre;
    public $programme;


    public $url;

    public $name;
    public $subject;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $student)
    {
        // $this->setDetails($course, $location);
        $this->student = $student;


        $this->course =  Course::find($this->student->registered_course ?? 1);
        $this->programme = Programme::find($this->course->programme_id ?? 1);
        $this->centre = Centre::find($this->course->centre_id ?? 1);
        $this->url = url('student/select-session/' . $this->student->userId);
        $this->subject = " One Million Coders Programme - {$this->programme->title}";
        $this->name = $this->student->name;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: $this->subject ?? 'Congratulations',
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
            markdown: 'mail.admitted',
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
}
