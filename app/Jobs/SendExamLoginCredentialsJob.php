<?php

namespace App\Jobs;

use App\Mail\ExamLoginCredentials;
use App\Models\Oex_exam_master;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendExamLoginCredentialsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $std;
    public $plainPassword;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    public function __construct(User $std, string $plainPassword)
    {
        $this->std = $std;
        $this->plainPassword = $plainPassword;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $deadline = $this->exam_deadline();
        Mail::to($this->std->email)->send(new ExamLoginCredentials($this->std, $this->plainPassword, $deadline));

        //     Log::info('Handling the job, sending email to ' . $this->std->email);

        // Mail::to($this->std->email)->send(new ExamLoginCredentials($this->std, $this->plainPassword));

        // Log::info('Email sent to ' . $this->std->email);


    }

    private function exam_deadline()
    {
        $registered = $this->std->created_at;
        $now = Carbon::now();
        $exam_id = $this->std->exam;

        $date = Oex_exam_master::find($exam_id)->exam_date;

        $leftToDeadline = $now->diffInHours(new Carbon($date));

        $deadline = $date;
        $hoursLeft = $leftToDeadline;

        $studentDeadline = (new Carbon($registered))->addDays(config(EXAM_DEADLINE_AFTER_REGISTRATION, 2));
        $studentHoursLeft = $now->diffInHours($studentDeadline);
        $studentDaysLeft = $now->diffInDays($studentDeadline);


        if ($studentHoursLeft < $leftToDeadline) {
            $deadline = $studentDeadline->toDateString();
            $hoursLeft = $studentHoursLeft;
        }

        $dealineText = $studentDaysLeft > 3 ? " $studentDaysLeft days" : "$hoursLeft hour(s)";

        return "$deadline in $dealineText";
    }
}
