<?php

namespace App\Jobs;

use App\Helpers\MailerHelper;
use App\Helpers\SmsHelper;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\ProgrammeBatch;
use App\Models\User;
use App\Models\UserAdmission;
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
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! $this->student) {
            return;
        }

        $lockKey = 'admission-lock-' . $this->student->id;
        $lock = Cache::lock($lockKey, 30);

        if (! $lock->get()) {
            Log::info('[ADMISSION] Duplicate dispatch skipped', ['user_id' => $this->student->id]);
            return;
        }

        try {
            $this->student = $this->student->fresh() ?? $this->student;
            $this->course = $this->resolveCourse();

            if (! $this->course) {
                Log::warning('[ADMISSION] Course not found for admission job', [
                    'user_id' => $this->student->userId,
                    'registered_course' => $this->student->registered_course,
                ]);

                return;
            }

            if ($this->session?->id) {
                $this->session = CourseSession::find($this->session->id) ?? $this->session;
            }

            $existingAdmission = UserAdmission::where('user_id', $this->student->userId)->first();

            if ($existingAdmission && ! $this->session && ! $this->programmeBatchId) {
                if (! $existingAdmission->email_sent) {
                    $this->sendAdmissionEmail();
                    $existingAdmission->update(['email_sent' => now()]);
                }

                return;
            }

            if ($this->programmeBatchId) {
                $this->handleProgrammeBatchBooking();

                return;
            }

            $admissionData = [
                'user_id' => $this->student->userId,
                'course_id' => $this->course->id,
                'email_sent' => now(),
            ];

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

            if ($this->session) {
                AdmitStudentJob::dispatch($admission);
            } else {
                $this->sendAdmissionEmail();
            }
        } finally {
            optional($lock)->release();
        }
    }

    private function handleProgrammeBatchBooking(): void
    {
        if (! $this->session) {
            Log::error('[ADMISSION] Booking requires a course session', [
                'user_id' => $this->student->id,
                'batch_id' => $this->programmeBatchId,
            ]);

            return;
        }

        $programmeBatch = ProgrammeBatch::find($this->programmeBatchId);

        if (! $programmeBatch) {
            Log::error('[ADMISSION] Programme batch not found', ['batch_id' => $this->programmeBatchId]);

            return;
        }

        try {
            $bookingService = app(BookingService::class);
            $bookingService->book($this->student, $this->course, $programmeBatch, $this->session);

            $admission = UserAdmission::where('user_id', $this->student->userId)
                ->where('course_id', $this->course->id)
                ->latest('id')
                ->first();

            if ($admission && $this->session) {
                AdmitStudentJob::dispatch($admission);

                return;
            }

            $this->sendAdmissionEmail($programmeBatch);
        } catch (\Throwable $e) {
            Log::error('[ADMISSION] Booking failed', [
                'user_id' => $this->student->id,
                'batch_id' => $this->programmeBatchId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveCourse(): ?Course
    {
        if ($this->course?->id) {
            return Course::with(['programme', 'centre'])->find($this->course->id) ?? $this->course;
        }

        if (! $this->student?->registered_course) {
            return null;
        }

        return Course::with(['programme', 'centre'])->find($this->student->registered_course);
    }

    private function sendAdmissionEmail(?ProgrammeBatch $programmeBatch = null): void
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
            $subject = "One Million Coders Programme - {$this->course->programme->title}";
            $emailData = $this->buildAdmissionEmailData($programmeBatch);

            MailerHelper::sendTemplateEmail(
                templateName: AFTER_ADMISSION_EMAIL,
                emails: $this->student->email,
                data: $emailData,
                subject: $subject
            );
        }
    }

    private function buildAdmissionEmailData(?ProgrammeBatch $programmeBatch = null): array
    {
        $startDate = $programmeBatch?->start_date
            ?? $this->course->start_date
            ?? $this->course->programme?->start_date;

        $endDate = $programmeBatch?->end_date
            ?? $this->course->end_date
            ?? $this->course->programme?->end_date;

        $sessionName = $this->session?->session ?? 'Session to be communicated';
        $sessionTime = $this->session?->course_time ?? 'Time will be communicated';
        $venue = $this->course->centre->title ?? 'Venue will be communicated';
        $programmeTitle = $this->course->programme->title ?? $this->course->course_name;
        $duration = $this->course->programme->duration ?? 'To be communicated';

        return [
            'name' => $this->student->name,
            'course_name' => $this->course->course_name,
            'programme_name' => $programmeTitle,
            'venue' => $venue,
            'courseSessionName' => $sessionName,
            'courseSessionTime' => $sessionTime,
            'start_date' => $this->formatDate($startDate),
            'end_date' => $this->formatDate($endDate),
            'url' => url('student/select-session/' . $this->student->userId),
            'duration' => $duration,
            'student_id' => $this->student->student_id ?? 'Pending',
        ];
    }

    private function formatDate($date): string
    {
        if (! $date) {
            return 'To be communicated';
        }

        return Carbon::parse($date)->format('l jS F, Y');
    }
}
