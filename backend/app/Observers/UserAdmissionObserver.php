<?php

namespace App\Observers;

use App\Models\Course;
use App\Models\OldAdmission;
use App\Models\User;
use App\Models\UserAdmission;

class UserAdmissionObserver
{
    public function created(UserAdmission $admission)
    {
        $course = $admission->course_id ? Course::find($admission->course_id) : null;

        OldAdmission::create([
            'user_id' => $admission->user_id,
            'course_id' => $admission->course_id,
            'centre_id' => $course?->centre_id,
            'session' => $admission->session,
            'status' => 'admitted',
            'support_status' => User::where('userId', $admission->user_id)->value('support'),
            'started_at' => now(),
        ]);
    }

    public function updated(UserAdmission $admission)
    {
        if ($admission->wasChanged('course_id')) {
            $oldCourseId = $admission->getOriginal('course_id');
            $this->closeOpenRow($admission->user_id, $oldCourseId);

            $course = $admission->course_id ? Course::find($admission->course_id) : null;

            OldAdmission::create([
                'user_id' => $admission->user_id,
                'course_id' => $admission->course_id,
                'centre_id' => $course?->centre_id,
                'session' => $admission->session,
                'status' => 'admitted',
                'support_status' => User::where('userId', $admission->user_id)->value('support'),
                'started_at' => now(),
            ]);

            return;
        }

        if ($admission->wasChanged('confirmed') && $admission->confirmed) {
            $row = $this->findOpenRow($admission->user_id, $admission->course_id);
            $row?->update([
                'started_at' => $admission->confirmed,
            ]);
        }

        if ($admission->wasChanged('session')) {
            $row = $this->findOpenRow($admission->user_id, $admission->course_id);
            $row?->update(['session' => $admission->session]);
        }
    }

    public function deleted(UserAdmission $admission)
    {
        $this->closeOpenRow($admission->user_id, $admission->course_id);
    }

    private function findOpenRow(string $userId, $courseId): ?OldAdmission
    {
        return OldAdmission::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->whereIn('status', ['admitted', 'confirmed'])
            ->orderByDesc('id')
            ->first();
    }

    private function closeOpenRow(string $userId, $courseId): void
    {
        $row = $this->findOpenRow($userId, $courseId);
        $row?->update([
            'status' => 'revoked',
            'ended_at' => now(),
        ]);
    }
}
