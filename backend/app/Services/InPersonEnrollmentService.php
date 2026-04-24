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
            $q->where(function ($query) {
                $query->where('capacity_pool', Booking::CAPACITY_POOL_RESERVED)
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('capacity_pool')
                            ->where('is_protocol', true);
                    });
            });
        }

        return $q->count();
    }

    /**
     * Return remaining seats for a given programme batch + course session.
     * In-person availability is scoped to the selected course session and its
     * own configured limit.
     */
    public function remainingSeats(int $programmeBatchId, int $courseSessionId, bool $forProtocolBooking = false): int
    {
        $batch = ProgrammeBatch::find($programmeBatchId);
        $cs = CourseSession::find($courseSessionId);

        if (! $batch || ! $cs) {
            return 0;
        }

        $centreId = $cs->centre_id ?: $cs->course?->centre_id;

        return $centreId
            ? $this->bookingService->getRemainingSeatsForCourseSession((int) $centreId, $batch->id, $cs->id, $forProtocolBooking)
            : 0;
    }

    /**
     * @throws Exception when validation fails or capacity is exhausted
     */
    public function enroll(User $user, Course $course, ProgrammeBatch $batch, CourseSession $centreSession, ?string $capacityPool = null): Booking
    {
        if (! $course->isInPersonProgramme()) {
            throw new Exception('Course is not an in-person programme.');
        }

        if ((bool) ($user->is_protocol ?? false) && $capacityPool === Booking::CAPACITY_POOL_STANDARD) {
            throw new Exception('Protocol in-person enrollment does not fall back to standard slots. Please choose another available recommendation.');
        }

        if (! $centreSession->status
            || strtolower(trim((string) ($centreSession->session ?? ''))) === 'online'
            || (int) $centreSession->course_id !== (int) $course->id
            || ($centreSession->centre_id !== null && (int) $centreSession->centre_id !== (int) $course->centre_id)) {
            throw new Exception('Invalid session for this course.');
        }

        if ((int) $course->programme_id !== (int) $batch->programme_id) {
            throw new Exception('Course does not belong to this programme batch.');
        }

        $booking = $this->bookingService->book(
            $user,
            $course,
            $batch,
            $centreSession,
            false,
            $capacityPool
        );

        if (! $booking instanceof Booking) {
            throw new Exception('Enrollment failed.');
        }

        return $booking;
    }
}
