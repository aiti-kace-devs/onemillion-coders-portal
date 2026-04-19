<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\ProgrammeBatch;
use App\Models\User;
use App\Models\UserAdmission;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InPersonEnrollmentService
{
    public function __construct(
        private BookingService $bookingService
    ) {}

    public function enrolledCount(int $programmeBatchId, int $courseSessionId): int
    {
        return Booking::query()
            ->where('programme_batch_id', $programmeBatchId)
            ->where('course_session_id', $courseSessionId)
            ->where('status', true)
            ->count();
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

        $centreId = (int) $course->centre_id;
        $lockKey = "in_person_enrollment:{$batch->id}:{$centreSession->id}";

        return Cache::lock($lockKey, 10)->block(5, function () use ($user, $course, $batch, $centreSession, $centreId) {
            return DB::transaction(function () use ($user, $course, $batch, $centreSession, $centreId) {
                $limit = $centreSession->limit;
                $hasLimit = $limit !== null && (int) $limit > 0;
                $enrolled = $this->enrolledCount($batch->id, $centreSession->id);

                if ($hasLimit && $enrolled >= (int) $limit) {
                    throw new Exception('Course session is full.');
                }

                $existing = Booking::where('user_id', $user->userId)
                    ->where('programme_batch_id', $batch->id)
                    ->first();

                if ($existing) {
                    return $existing;
                }

                $previous = Booking::where('user_id', $user->userId)
                    ->where('programme_batch_id', '!=', $batch->id)
                    ->first();

                if ($previous) {
                    $this->bookingService->cancel($previous);
                }

                $courseType = Booking::resolveCourseType($course->id);

                $user->registered_course = $course->id;
                $user->shortlist = true;
                $user->save();

                $admission = UserAdmission::updateOrCreate(
                    ['user_id' => $user->userId],
                    [
                        'course_id' => $course->id,
                        'programme_batch_id' => $batch->id,
                        'session' => $centreSession->id,
                        'email_sent' => now(),
                        'confirmed' => now(),
                    ]
                );

                return Booking::create([
                    'user_id' => $user->userId,
                    'programme_batch_id' => $batch->id,
                    'course_session_id' => $centreSession->id,
                    'master_session_id' => null,
                    'centre_id' => $centreId,
                    'course_id' => $course->id,
                    'course_type' => $courseType,
                    'status' => true,
                    'booked_at' => now(),
                    'user_admission_id' => $admission->id,
                ]);
            });
        });
    }
}
