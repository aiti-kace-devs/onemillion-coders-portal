<?php

namespace App\Services\Scheduling;

use App\Models\Course;
use App\Models\Programme;
use App\Models\ProgrammeQuota;
use App\Models\User;
use App\Models\UserAdmission;

class ProgrammeQuotaService
{
    /**
     * Remote-only online learners use nationwide quota; online + support (centre attendance) uses per-centre
     * quota when the course row has a centre_id, same as in-person.
     */
    public function resolveApplicableQuota(Programme $programme, Course $course, ?User $user = null): ?ProgrammeQuota
    {
        $query = ProgrammeQuota::query()->where('programme_id', $programme->id);

        $onlineAttendsCentre = $course->isOnlineProgramme()
            && $user
            && $user->support
            && $course->centre_id;

        if ($course->isOnlineProgramme() && ! $onlineAttendsCentre) {
            $query->where('scope', ProgrammeQuota::SCOPE_NATIONWIDE)->whereNull('centre_id');
        } else {
            if (! $course->centre_id) {
                return null;
            }
            $query->where('scope', ProgrammeQuota::SCOPE_PER_CENTRE)->where('centre_id', $course->centre_id);
        }

        $query->where(function ($q) use ($course) {
            $q->whereNull('batch_id')->orWhere('batch_id', $course->batch_id);
        });

        $candidates = $query->get();
        if ($candidates->isEmpty()) {
            return null;
        }

        $exactBatch = $candidates->firstWhere('batch_id', $course->batch_id);

        return $exactBatch ?? $candidates->firstWhere('batch_id', null) ?? $candidates->first();
    }

    public function countConfirmedAgainstQuota(ProgrammeQuota $quota): int
    {
        $q = UserAdmission::query()
            ->whereNotNull('user_admission.confirmed')
            ->join('courses', 'user_admission.course_id', '=', 'courses.id')
            ->where('courses.programme_id', $quota->programme_id);

        if ($quota->batch_id) {
            $q->where('courses.batch_id', $quota->batch_id);
        }

        if ($quota->scope === ProgrammeQuota::SCOPE_PER_CENTRE && $quota->centre_id) {
            $q->where('courses.centre_id', $quota->centre_id);
        }

        return $q->count();
    }

    public function remainingForCourse(Programme $programme, Course $course, ?User $user = null): array
    {
        $quota = $this->resolveApplicableQuota($programme, $course, $user);
        if (! $quota) {
            return [
                'applies' => false,
                'max' => null,
                'used' => null,
                'remaining' => null,
            ];
        }

        $used = $this->countConfirmedAgainstQuota($quota);
        $remaining = max(0, $quota->max_enrollments - $used);

        return [
            'applies' => true,
            'max' => $quota->max_enrollments,
            'used' => $used,
            'remaining' => $remaining,
        ];
    }

    public function hasCapacityForNewConfirmation(Programme $programme, Course $course, ?User $user = null): bool
    {
        $info = $this->remainingForCourse($programme, $course, $user);
        if (! $info['applies']) {
            return true;
        }

        return $info['remaining'] > 0;
    }
}
