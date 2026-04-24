<?php

namespace App\Jobs;

use App\Http\Controllers\NotificationController;
use App\Models\AdmissionWaitlist;
use App\Models\Booking;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\MasterSession;
use App\Models\ProgrammeBatch;
use App\Models\User;
use App\Models\UserAdmission;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\StudentIdGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HandleEnrollmentJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ?UserAdmission $admission = null;

    public ?User $enrolledUser = null;

    public ?Booking $booking = null;

    public function __construct(
        public User $user,
        public Course $course,
        public ProgrammeBatch $batch,
        public ?int $sessionId = null,
        public bool $isSelfPaced = false,
        public bool $withSupport = false,
        public bool $isInPerson = false,
        public bool $isProtocolBooking = false,
        public ?string $capacityPool = null
    ) {
    }

    public function handle(): ?User
    {
        $studentId = StudentIdGenerator::generate($this->user, $this->course);
        $centreId = $this->course->centre_id;
        $bookingService = app(BookingService::class);

        $this->cancelPreviousBookingIfNeeded();

        $this->user->registered_course = $this->course->id;
        $this->user->student_id = $studentId;
        $this->user->shortlist = true;

        if ($this->isSelfPaced) {
            $this->user->support = false;
        } elseif ($this->withSupport) {
            $this->user->support = true;
        }

        $this->user->save();

        if ($this->shouldCreateBooking()) {
            $session = $this->resolveSessionModel();

            if (! $session) {
                throw new \Exception('The selected session id is invalid.');
            }

            $this->booking = $bookingService->book(
                $this->user,
                $this->course,
                $this->batch,
                $session,
                $this->isProtocolBooking,
                $this->capacityPool
            );

            $this->admission = $this->booking?->userAdmission?->fresh()
                ?? UserAdmission::where('user_id', $this->user->userId)->first();
        } else {
            $this->admission = UserAdmission::updateOrCreate(
                ['user_id' => $this->user->userId],
                [
                    'course_id' => $this->course->id,
                    'programme_batch_id' => $this->batch->id,
                    'email_sent' => now(),
                    'confirmed' => now(),
                    'session' => null,
                ]
            );

            AvailabilityService::clearCache(
                $centreId,
                $this->course->id,
                $this->batch->start_date,
                $this->batch->end_date
            );

            AdmissionWaitlist::where('user_id', $this->user->userId)->delete();
        }

        NotificationController::notify(
            $this->user->id,
            'COURSE_SELECTION',
            'Enrollment Confirmed',
            $this->buildEnrollmentMessage()
        );

        AdmissionWaitlist::where('user_id', $this->user->userId)->delete();

        if ($this->admission) {
            AdmitStudentJob::dispatch($this->admission);
        }

        Log::info('Enrollment handled successfully', [
            'user_id' => $this->user->userId,
            'course_id' => $this->course->id,
            'batch_id' => $this->batch->id,
            'student_id' => $studentId,
            'session_id' => $this->sessionId,
            'is_self_paced' => $this->isSelfPaced,
            'with_support' => $this->withSupport,
            'is_in_person' => $this->isInPerson,
            'is_protocol_booking' => $this->isProtocolBooking,
            'capacity_pool' => $this->booking?->capacity_pool,
            'booking_id' => $this->booking?->id,
        ]);

        $this->enrolledUser = $this->isInPerson ? null : $this->user->fresh();

        return $this->enrolledUser;
    }

    protected function shouldCreateBooking(): bool
    {
        return $this->sessionId !== null;
    }

    protected function cancelPreviousBookingIfNeeded(): void
    {
        if (! $this->shouldCreateBooking()) {
            return;
        }

        $previous = Booking::where('user_id', $this->user->userId)
            ->where('programme_batch_id', '!=', $this->batch->id)
            ->where('status', true)
            ->first();

        if ($previous) {
            app(BookingService::class)->cancel($previous);
        }
    }

    protected function resolveCapacityPool(): string
    {
        $requestedPool = in_array($this->capacityPool, [
            Booking::CAPACITY_POOL_RESERVED,
            Booking::CAPACITY_POOL_STANDARD,
        ], true) ? $this->capacityPool : null;

        if (! $this->isProtocolBooking) {
            return Booking::CAPACITY_POOL_STANDARD;
        }

        return $requestedPool ?: Booking::CAPACITY_POOL_RESERVED;
    }

    protected function resolveSessionModel(): CourseSession|MasterSession|null
    {
        if ($this->sessionId === null) {
            return null;
        }

        if (! $this->isInPerson) {
            return MasterSession::find($this->sessionId);
        }

        $session = CourseSession::find($this->sessionId);
        if (! $session || ! $session->status) {
            return null;
        }

        if (strtolower(trim((string) ($session->session ?? ''))) === 'online') {
            return null;
        }

        if ((int) $session->course_id !== (int) $this->course->id) {
            return null;
        }

        if ($session->centre_id !== null && (int) $session->centre_id !== (int) $this->course->centre_id) {
            return null;
        }

        return $session;
    }

    protected function clearRemainingSeatCaches(int $centreId, int $batchId, int $sessionId): void
    {
        Cache::forget("remaining_seats:{$centreId}:{$batchId}:{$sessionId}");

        foreach (['standard', 'protocol'] as $mode) {
            Cache::forget("remaining_seats:{$centreId}:{$batchId}:{$sessionId}:{$mode}");
            Cache::forget("remaining_seats:course_session:{$centreId}:{$batchId}:{$sessionId}:{$mode}");
        }
    }

    protected function buildEnrollmentMessage(): string
    {
        $context = '';

        if ($this->isSelfPaced) {
            $context = ' (self-paced)';
        } elseif ($this->withSupport) {
            $context = ' with centre-based support';
        } elseif ($this->isInPerson) {
            $context = ' for your in-person programme';
        }

        return 'You have successfully enrolled in <strong>' . e($this->course->course_name) . '</strong>'
            . $context
            . '. You will be notified of next steps.';
    }
}
