<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\CourseSession;

class UpdateCourseNamesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $courses = Course::all();
        $courseCount = 0;
        foreach ($courses as $course) {
            $course->save();
            $courseCount++;
        }

        $sessions = CourseSession::all();
        $sessionCount = 0;
        foreach ($sessions as $session) {
            $session->setSessionName();
            $session->save();
            $sessionCount++;
        }

        $this->command->info("Successfully refreshed names for {$courseCount} Courses and {$sessionCount} Course Sessions.");
    }
}
