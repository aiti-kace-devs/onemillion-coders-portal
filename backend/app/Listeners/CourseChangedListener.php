<?php

namespace App\Listeners;

use App\Events\CourseChanged;
use App\Models\OexExamMaster;
use App\Models\UserAdmission;
use App\Models\UserExam;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CourseChangedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CourseChanged $event): void
    {
        $user = $event->user;

        $exam = OexExamMaster::inRandomOrder()->first();

        if ($exam) {
            $user->exam = $exam->id;
            $user->shortlist = false;
            $user->save();

            UserExam::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'exam_id' => $exam->id,
                ],
                [
                    'std_status' => 1,
                    'exam_joined' => 0,
                    'started' => null,
                    'submitted' => null,
                ]
            );

            UserAdmission::updateOrCreate(
                ['user_id' => $user->userId],
                [
                    'course_id' => $user->registered_course,
                    'session' => null,
                    'confirmed' => null,
                    'location' => null,
                    'email_sent' => null
                ]
            );
        }
    }
}
