<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;

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
        $count = 0;

        foreach ($courses as $course) {
            $course->save();
            $count++;
        }

        $this->command->info("Updated {$count} course names to the new format: Programme - (Centre)");
    }
}
