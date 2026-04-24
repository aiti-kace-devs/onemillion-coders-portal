<?php

namespace App\Jobs;

use App\Helpers\MailerHelper;
use App\Helpers\SmsHelper;
use App\Http\Controllers\NotificationController;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\EmailTemplate;
use App\Models\MasterSession;
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
        $this->session = $this->session 
        ?? CourseSession::find($this->admission->session) 
        ?? MasterSession::find($this->admission->session);
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
        $subject = 'Admission Confirmation Successful';
        $confirmationMessage = $this->getConfirmationTemplateContent($confirmationData);

        if (config(SEND_EMAIL_AFTER_ADMISSION_CONFIRMATION, true)) {
            MailerHelper::sendTemplateEmail(
                templateName: AFTER_ADMISSION_CONFIRMATION_EMAIL,
                emails: $this->student->email,
                data: $confirmationData,
                subject: $subject,
                createNotification: false
            );
        }

        if (! empty($confirmationMessage)) {
            NotificationController::notify(
                $this->student->id,
                'COURSE_SELECTION',
                'Admission Confirmation Successful',
                $confirmationMessage,
                'normal',
                'email'
            );
        }

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

    private function getConfirmationTemplateContent(array $confirmationData): string
    {
        $template = EmailTemplate::where('name', AFTER_ADMISSION_CONFIRMATION_EMAIL)->value('content');

        if (! $template) {
            return '';
        }

        return MailerHelper::replaceVariables($template, $confirmationData);
    }

private function buildConfirmationEmailData(): array
{

    // Default fallbacks
    $sessionName = 'Your assigned training session';
    $sessionTime = 'Time will be communicated';

    if (!empty($this->admission->session) && $this->session) {
        if ($this->session instanceof CourseSession) {
            $sessionName = $this->session->name ?? $this->session->session ?? $sessionName;
            $sessionTime = $this->session->course_time ?? $sessionTime;
        } elseif ($this->session instanceof MasterSession) {
            $sessionName = $this->session->session_type ?? $this->session->master_name ?? $sessionName;
            $sessionTime = $this->session->time ?? $sessionTime;
        }
    }

    $startDate = $this->programmeBatch?->start_date ?? $this->course->start_date ?? $this->course->programme?->start_date;
    $endDate = $this->programmeBatch?->end_date ?? $this->course->end_date ?? $this->course->programme?->end_date;
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
