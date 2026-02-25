<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\UserAdmission;
use App\Models\User;
use App\Models\CourseSession;
use App\Helpers\MailerHelper;
use App\Helpers\SmsHelper;
use App\Models\AdmissionRun;
use App\Models\Course;


class AdmitStudentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    public $admission, $course, $session, $student;


    public function __construct(UserAdmission $admission)
    {
        $this->admission = $admission;
        $this->student = User::where('userId', $this->admission->user_id)->first();
        $this->course = Course::find($this->admission->course_id);
        $this->session = CourseSession::find($this->admission->session);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        if (!$this->student || !$this->course || !$this->session) {
            return;
        }

        // update admission run status
        $admissionRun = AdmissionRun::find($this->admission->admission_run_id);

        if ($admissionRun) {
            $admissionRun->increment('admitted_count');
            $admissionRun->updateStats();
        }
        $this->sendConfirmationEmail();
    }

    private function sendConfirmationEmail()
    {
        if (config(SEND_EMAIL_AFTER_ADMISSION_CONFIRMATION, true)) {
            MailerHelper::sendTemplateEmail(AFTER_ADMISSION_CONFIRMATION_EMAIL, $this->student->email, [
                'name' => $this->student->name,
                'courseSessioName' => $this->session->name,
                'courseSessionTime' => $this->session->course_time,
                'link' => $this->session->link
            ], 'Admission Confirmation Successful');
        }

        if (config(SEND_SMS_AFTER_ADMISSION_CONFIRMATION, true)) {
            $smsContent = SmsHelper::getTemplate(AFTER_ADMISSION_CONFIRMATION_SMS, [
                'name' => $this->student->name,
                'course' => $this->course->programme->title,
            ]) ?? '';;
            $details['message'] = $smsContent;
            $details['phonenumber'] = $this->student->mobile_no;

            SendSMSAfterRegistrationJob::dispatch($details);
        }
    }
}
