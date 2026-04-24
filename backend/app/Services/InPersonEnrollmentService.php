<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\ProgrammeBatch;
use App\Models\User;
use Exception;

class InPersonEnrollmentService
{
    public function __construct(
        private BookingService $bookingService
    ) {}

    public function enrolledCount(int $programmeBatchId, int $courseSessionId, bool $forProtocolBooking = false): int
    {
        $q = Booking::query()
            ->where('programme_batch_id', $programmeBatchId)
            ->where('course_session_id', $courseSessionId)
            ->where('status', true);

        if ($forProtocolBooking) {
            $q->where('is_protocol', true);
        }

        return $q->count();
    }

    /**
     * Return remaining seats for a given programme batch + course session.
     * This is a thin wrapper over BookingService::getRemainingSeats that
     * accepts CourseSession ids (centre sessions) and resolves the master
     * session id used by the occupancy system.
     */
    public function remainingSeats(int $programmeBatchId, int $courseSessionId, bool $forProtocolBooking = false): int
    {
        $batch = ProgrammeBatch::find($programmeBatchId);
        $cs = CourseSession::find($courseSessionId);

        if (! $batch || ! $cs) {
            return 0;
        }

        $centreId = $cs->centre_id;

        // Resolve master session id for occupancy calculations. If none, use the session id.
        $masterSessionId = $cs->masterSession?->id ?? $cs->id;

        return $this->bookingService->getRemainingSeats((int) $centreId, $batch->id, $masterSessionId, $forProtocolBooking);
    }

    /**
     * @throws Exception when validation fails or capacity is exhausted
     */
    public function enroll(User $user, Course $course, ProgrammeBatch $batch, CourseSession $centreSession): Booking
    {
        if (! $course->isInPersonProgramme()) {
            throw new Exception('Course is not an in-person programme.');
        }

        if ($centreSession->session_type !== CourseSession::TYPE_CENTRE
            || (int) $centreSession->course_id !== (int) $course->id
            || (int) $centreSession->centre_id !== (int) $course->centre_id) {
            throw new Exception('Invalid session for this course.');
        }

        if ((int) $course->programme_id !== (int) $batch->programme_id) {
            throw new Exception('Course does not belong to this programme batch.');
        }

        $booking = $this->bookingService->book($user, $course, $batch, $centreSession, false);

        if (! $booking instanceof Booking) {
            throw new Exception('Enrollment failed.');
        }

        return $booking;
    }
}
