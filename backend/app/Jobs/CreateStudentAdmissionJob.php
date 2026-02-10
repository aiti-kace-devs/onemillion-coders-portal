<?php

namespace App\Jobs;

use App\Helpers\MailerHelper;
use App\Helpers\SmsHelper;
use App\Models\AdmissionRun;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\User;
use App\Models\UserAdmission;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateStudentAdmissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public $student,
        public $course = null,
        public ?CourseSession $session = null,
        public string $source = 'manual',
        public ?int $admissionRunId = null
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $student = $this->student instanceof User ? $this->student : User::find($this->student);

        if (!$student) return;

        $course = $this->course instanceof Course ? $this->course : Course::find($student->registered_course);
        if (!$course) return;

        $changingAdmission = false;


        $existingAdmission = UserAdmission::where('user_id', $student->userId)
            ->where('course_id', $course->id)
            ->first();

        if ($existingAdmission && !$this->session) {
            if (false == $existingAdmission->email_sent) {
                $this->sendAdmissionEmail();
                $existingAdmission->update(['email_sent' => now()]);
            }
            return;
        }

        $admissionData = [
            'user_id' => $this->student->userId,
            'course_id' => $course->id,
            'email_sent' => now(),
            'admission_source' => $this->source,
            'admission_run_id' => $this->admissionRunId,
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
        if (config(SEND_SMS_AFTER_ADMISSION_CREATION, true)) {
            $smsContent = SmsHelper::getTemplate(AFTER_ADMISSION_SMS, [
                'name' => $this->student->name,
            ]) ?? '';
            $details['message'] = $smsContent;
            $details['phonenumber'] = $this->student->mobile_no;
            SendSMSAfterRegistrationJob::dispatch($details);
        }

        if (config(SEND_EMAIL_AFTER_ADMISSION_CREATION, true)) {
            $subject = " One Million Coders Programme - {$this->course->programme->title}";
            MailerHelper::sendTemplateEmail(templateName: AFTER_ADMISSION_EMAIL, emails: $this->student->email, data: [
                'name' => $this->student->name,
                'course_name' => $this->course->course_name,
                'venue' => $this->course->centre->title,
                'start_date' => (new Carbon($this->course->start_date ?? $this->course->programme->start_date))->format('l jS F, Y'),
                'url' => url('student/select-session/' . $this->student->userId),
                'duration' => $this->course->duration ?? $this->course->programme->duration
            ], subject: $subject);

            AdmissionRun::find($this->admissionRunId)?->increment('emailed_count');
        }
    }
}
