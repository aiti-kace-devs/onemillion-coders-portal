<?php

namespace App\Console\Commands;

use App\Jobs\CreateStudentAdmissionJob;
use App\Models\Branch;
use App\Models\Centre;
use App\Models\Course;
use App\Models\User;
use Illuminate\Console\Command;

class AdmitStudentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:admit-students {course?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $course_id = $this->argument('course');

        if (!$course_id) {
            // select courses and display in console by selecting branch, center then course
            $branches = Branch::all();
            $branchesOptions = $branches->map(function ($branch) {
                return ['id' => $branch->id, 'data' => $branch->title];
            });

            $selectedBranch = $this->askForOptionSelection($branchesOptions->all(), 'Branch');

            // select centre and display in console by filtering based on branch
            $centres = Centre::where('branch_id', $selectedBranch)->get()->map(function ($centre) {
                return ['id' => $centre->id, 'data' => $centre->title];
            });

            $selectedCentre = $this->askForOptionSelection($centres->all(), 'Centre');

            // select course and display in console by filtering based on centre
            $courses = Course::where('centre_id', $selectedCentre)->get()->map(function ($course) {
                return ['id' => $course->id, 'data' => $course->course_name];
            });

            $course_id = $this->askForOptionSelection($courses->all(), 'Course');
        }



        Course::findOrFail($course_id);

        $shortlistedStudents = User::where('shortlist', '1')->where('registered_course', $course_id)
            // ->leftJoin('user_admission', 'users.userId', '=', 'user_admission.user_id')
            ->whereDoesntHave('admission')->get();

        $this->info($shortlistedStudents->count() . ' student(s) yet to be admitted');

        $this->withProgressBar($shortlistedStudents, function ($student) {
            CreateStudentAdmissionJob::dispatch($student);
        });

        $this->info('All students admitted successfully');
    }

    private function askForOptionSelection($options, $message)
    {
        // $this->line("Select a tenant below. Type in the number to select tenant");
        $this->line("*****************************************************");
        $this->line("Select One : [Number] $message");

        foreach ($options as $option) {
            $this->comment("[{$option['id']}] {$option['data']}");
        }
        $this->line("*****************************************************");
        $selection = $this->ask("Type number : ");
        return $selection;
    }
}
