<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\User;
use App\Services\StudentIdGenerator;
use Illuminate\Console\Command;

class BackfillStudentIdsCommand extends Command
{
    protected $signature = 'students:backfill-ids';

    protected $description = 'Generate student IDs for admitted students who do not have one';

    public function handle(): int
    {
        $students = User::whereNull('student_id')
            ->whereNotNull('registered_course')
            ->whereHas('admission')
            ->get();

        if ($students->isEmpty()) {
            $this->info('No students found without a student ID.');
            return self::SUCCESS;
        }

        $this->info("Found {$students->count()} student(s) without a student ID.");

        $generated = 0;
        $skipped = 0;

        foreach ($students as $student) {
            $course = Course::find($student->registered_course);
            $studentId = StudentIdGenerator::generate($student, $course);

            if ($studentId) {
                $student->student_id = $studentId;
                $student->saveQuietly();
                $generated++;
            } else {
                $skipped++;
            }
        }

        $this->info("Done. Generated: {$generated}, Skipped (no batch): {$skipped}");

        return self::SUCCESS;
    }
}
