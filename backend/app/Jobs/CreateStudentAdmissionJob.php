<?php

namespace App\Jobs;

use App\Helpers\MailerHelper;
use App\Helpers\SmsHelper;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\User;
use App\Models\UserAdmission;
use App\Models\ProgrammeBatch;
use App\Services\StudentIdGenerator;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CreateStudentAdmissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?User $student,
        public ?Course $course = null,
        public ?CourseSession $session = null,
        public ?int $programmeBatchId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->student) return;

        $lockKey = 'admission-lock-' . $this->student->id;
        if (!Cache::lock($lockKey, 30)->get()) {
            Log::info('[ADMISSION] Duplicate dispatch skipped', ['user_id' => $this->student->id]);
            return;
        }

        $course = $this->course ?? Course::find($this->student->registered_course);
        if (!$course) return;

        $changingAdmission = false;

        $existingAdmission = UserAdmission::where('user_id', $this->student->userId)->first();
        if ($existingAdmission && !$this->session && !$this->programmeBatchId) {
            if (!$existingAdmission->email_sent) {
                $this->sendAdmissionEmail();
                $existingAdmission->update(['email_sent' => now()]);
            }
            return;
        }

        // If a programme_batch_id is provided, use BookingService to book (requires a course session).
        if ($this->programmeBatchId) {
            if (!$this->session) {
                Log::error('[ADMISSION] Booking requires a course session', [
                    'user_id' => $this->student->id,
                    'batch_id' => $this->programmeBatchId,
                ]);
                return;
            }

            $programmeBatch = ProgrammeBatch::find($this->programmeBatchId);
            if (!$programmeBatch) {
                Log::error('[ADMISSION] Programme batch not found', ['batch_id' => $this->programmeBatchId]);
                return;
            }

            try {
                $bookingService = app(BookingService::class);
                $bookingService->book($this->student, $course, $programmeBatch, $this->session);
                $this->sendAdmissionEmail();
                return;
            } catch (\Exception $e) {
                Log::error('[ADMISSION] Booking failed', [
                    'user_id' => $this->student->id,
                    'batch_id' => $this->programmeBatchId,
                    'error' => $e->getMessage(),
                ]);
                return;
            }
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
            $admissionData['confirmed'] = now();
        }

        if ($existingAdmission) {
            $existingAdmission->update($admissionData);
            $existingAdmission->refresh();
            $admission = $existingAdmission;
        } else {
            $admission = UserAdmission::create($admissionData);
        }

        // Generate a new student ID on every admission
        $studentId = StudentIdGenerator::generate($this->student, $course);
        if ($studentId) {
            $this->student->student_id = $studentId;
            $this->student->saveQuietly();
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
        }
    }
}
