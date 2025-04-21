<?php

namespace App\Jobs;

use App\Helpers\SmsHelper;
use App\Mail\StudentAdmitted;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\User;
use App\Models\UserAdmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class CreateStudentAdmissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public ?User $student, public ?Course $course = null, public ?CourseSession $session = null)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->student) return;

        $course = $this->course ?? Course::find($this->student->registered_course);
        if (!$course) return;

        $changingAdmission = false;


        $existingAdmission = UserAdmission::where('user_id', $this->student->userId)->first();
        if ($existingAdmission && !$this->session) {
            if (!$existingAdmission->email_sent) {
                $this->sendAdmissionEmail();
                $existingAdmission->update(['email_sent' => now()]);
            }
            return;
        }

        $admissionData = [
            'user_id' => $this->student->userId,
            'course_id' => $course->id,
            'email_sent' => now(),
        ];

        if ($existingAdmission?->session) {
            $changingAdmission = true; // TODO: use this to determine if an email should be sent
        }

        if ($this->session) {

            $admissionData['session'] = $this->session->id;
            $admissionData['location'] = $this->course->centre->title;
            $admissionData['confirmed'] = now();
        }

        if ($existingAdmission) {
            $existingAdmission->update($admissionData);
            $existingAdmission->refresh();
            $admission = $existingAdmission;
        } else {
            $admission = UserAdmission::create($admissionData);
        }


        if ($this->session) {
            AdmitStudentJob::dispatch($admission);
        } else {
            $this->sendAdmissionEmail();
        }
    }


    private function sendAdmissionEmail()
    {
        if (config(SEND_EMAIL_AFTER_ADMISSION_CREATION, true)) {
            Mail::to($this->student->email)->bcc(env('MAIL_FROM_ADDRESS', 'no-reply@example.com'))
                ->send(new StudentAdmitted(
                    $this->student
                ));
        }

        if (config(SEND_SMS_AFTER_ADMISSION_CREATION, true)) {
            $smsContent = SmsHelper::getTemplate(AFTER_ADMISSION_SMS, [
                'name' => $this->student->name,
            ]) ?? '';;
            $details['message'] = $smsContent;
            $details['phonenumber'] = $this->student->mobile_no;

            SendSMSAfterRegistrationJob::dispatch($details);
        }
    }
}
