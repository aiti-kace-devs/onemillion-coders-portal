<?php

namespace App\Jobs;

use App\Helpers\MailerHelper;
use App\Helpers\SmsHelper;
use App\Http\Controllers\NotificationController;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\ProgrammeBatch;
use App\Models\User;
use App\Models\UserAdmission;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AdmitStudentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $admission, $course, $session, $student, $programmeBatch;

    /**
     * Create a new job instance.
     */
    public function __construct(UserAdmission $admission)
    {
        $this->admission = $admission;
        $this->student = User::where('userId', $this->admission->user_id)->first();
        $this->course = Course::with(['programme', 'centre'])->find($this->admission->course_id);
        $this->session = CourseSession::find($this->admission->session);
        $this->programmeBatch = ProgrammeBatch::find($this->admission->programme_batch_id);
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        if (! $this->student || ! $this->course) {
            return;
        }

        $this->sendConfirmationEmail();
    }

    private function sendConfirmationEmail(): void
    {
        $confirmationData = $this->buildConfirmationEmailData();

        if (config(SEND_EMAIL_AFTER_ADMISSION_CONFIRMATION, true)) {
            MailerHelper::sendGenericTemplateEmail(
                $this->student->email,
                $this->buildConfirmationEmailMessage($confirmationData),
                'Admission Confirmation Successful',
                false,
                $confirmationData,
                false
            );
        }

        NotificationController::notify(
            $this->student->id,
            'COURSE_SELECTION',
            'Enrollment Confirmed',
            $this->buildConfirmationNotificationMessage($confirmationData),
            'normal',
            'email'
        );

        if (config(SEND_SMS_AFTER_ADMISSION_CONFIRMATION, true)) {
            $smsContent = SmsHelper::getTemplate(AFTER_ADMISSION_CONFIRMATION_SMS, [
                'name' => $this->student->name,
                'course' => $this->course->programme->title,
            ]) ?? '';

            $details['message'] = $smsContent;
            $details['phonenumber'] = $this->student->mobile_no;

            SendSMSAfterRegistrationJob::dispatch($details);
        }
    }

    private function buildConfirmationEmailMessage(array $confirmationData): string
    {
        $message = <<<EOD
# Dear {$confirmationData['name']},

Congratulations on your successful enrollment in **{$confirmationData['course_name']}**.

This is to confirm your training details for **{$confirmationData['courseSessioName']}**. <br>
**Time:** {$confirmationData['courseSessionTime']} <br>
**Start Date:** {$confirmationData['start_date']} <br>
**End Date:** {$confirmationData['end_date']} <br>
**Training Duration:** {$confirmationData['duration']} <br>
**Venue:** {$confirmationData['venue']} <br>
**Learning Mode:** {$confirmationData['support_mode']} <br>
**Student ID:** {$confirmationData['student_id']}

[component]: # ('mail::panel')
Please keep this information for your records and ensure you are available to participate as scheduled.
[endcomponent]: #
EOD;

        if (! empty($confirmationData['link'])) {
            $message .= <<<EOD


You can join the official WhatsApp group for this session by clicking the button below.

[component]: # ('mail::button', ['url' => '{$confirmationData['link']}'])
Join WhatsApp Group
[endcomponent]: #
EOD;
        }

        $message .= <<<EOD


[component]: # ('mail::panel')
If any of the details above change, you will be notified through your registered email address and phone number.
[endcomponent]: #
EOD;

        return $message;
    }

    private function buildConfirmationNotificationMessage(array $confirmationData): string
    {
        $message = <<<EOD
Dear {$confirmationData['name']},<br><br>

Congratulations on your successful enrollment in **{$confirmationData['course_name']}**.

This is to confirm your training details for **{$confirmationData['courseSessioName']}**. <br>
**Time:** {$confirmationData['courseSessionTime']} <br>
**Start Date:** {$confirmationData['start_date']} <br>
**End Date:** {$confirmationData['end_date']} <br>
**Training Duration:** {$confirmationData['duration']} <br>
**Venue:** {$confirmationData['venue']} <br>
**Learning Mode:** {$confirmationData['support_mode']} <br>
**Student ID:** {$confirmationData['student_id']}

[component]: # ('mail::panel')
Please keep this information for your records and ensure you are available to participate as scheduled.
[endcomponent]: #
EOD;

        if (! empty($confirmationData['link'])) {
            $message .= <<<EOD


You can join the official WhatsApp group for this session by clicking the button below.

[component]: # ('mail::button', ['url' => '{$confirmationData['link']}'])
Join WhatsApp Group
[endcomponent]: #
EOD;
        }

        $message .= <<<EOD


[component]: # ('mail::panel')
If any of the details above change, you will be notified through your registered email address and phone number.
[endcomponent]: #
EOD;

        return $message;
    }

    private function buildConfirmationEmailData(): array
    {
        $startDate = $this->programmeBatch?->start_date
            ?? $this->course->start_date
            ?? $this->course->programme?->start_date;

        $endDate = $this->programmeBatch?->end_date
            ?? $this->course->end_date
            ?? $this->course->programme?->end_date;

        $sessionName = $this->session?->name ?? 'Your assigned training session';
        $sessionTime = $this->session?->course_time ?? 'Time will be communicated';
        $programmeTitle = $this->course->programme->title ?? $this->course->course_name;
        $venue = $this->course->centre->title ?? 'Venue will be communicated';
        $duration = $this->course->programme->duration ?? 'To be communicated';
        $link = $this->session?->link ?? '';
        $supportMode = $this->resolveSupportMode();

        return [
            'name' => $this->student->name,
            'student_id' => $this->student->student_id ?? 'Pending',
            'course_name' => $this->course->course_name,
            'programme_name' => $programmeTitle,
            'courseSessionName' => $sessionName,
            'courseSessioName' => $sessionName,
            'courseSessionTime' => $sessionTime,
            'start_date' => $this->formatDate($startDate),
            'end_date' => $this->formatDate($endDate),
            'duration' => $duration,
            'venue' => $venue,
            'link' => $link,
            'support_mode' => $supportMode,
        ];
    }

    private function resolveSupportMode(): string
    {
        if ($this->student?->support) {
            return 'Centre-based support';
        }

        if (! $this->session) {
            return 'Self-paced';
        }

        return 'Scheduled session';
    }

    private function formatDate($date): string
    {
        if (! $date) {
            return 'To be communicated';
        }

        return Carbon::parse($date)->format('l jS F, Y');
    }
}
