<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendStudentFeedback;
use App\Models\User;
use App\Models\user_exam;
use Carbon\Carbon;

class SendFeedback extends Command
{
    protected $signature = 'email:sendFeedback';
    protected $description = 'Send BCC email to students who completed their exams in the last hour';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if (!config(SEND_EMAIL_AFTER_EXAM_SUBMISSION, true)) return;

        $completedExams = user_exam::whereNotNull('submitted')
            ->whereNull('user_feedback')->limit(300)
            ->get();
        if ($completedExams->isEmpty()) {
            $this->info('No students completed exams in the last hour.');
            return;
        }

        $userEmails = User::select('email')
            ->whereIn(
                'id',
                $completedExams->pluck('user_id')->all()
            )
            ->get()->pluck('email')->all();

        if (count($userEmails) > 0) {
            Mail::to(env('MAIL_FROM_ADDRESS'))->bcc($userEmails)->send(new SendStudentFeedback());
            user_exam::whereIn('id', $completedExams->pluck('id')->all())->update([
                'user_feedback' => Carbon::now()->toDateTimeString()
            ]);

            $this->info('Emails sent to students who completed exams in the last hour.');
        } else {
            $this->info('No valid email addresses found.');
        }
    }
}
