<?php

namespace App\Console\Commands;

use App\Helpers\MailerHelper;
use Illuminate\Console\Command;
use App\Models\User;
use App\Models\user_exam;
use Carbon\Carbon;

class SendFeedback extends Command
{
    protected $signature = 'email:sendFeedback';
    protected $description = 'Send BCC email to students who completed their exams since last check';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if (!config(SEND_EMAIL_AFTER_EXAM_SUBMISSION, true)) return;
        $limit = app()->isLocal() ? 50 : 200;
        $completedExams = user_exam::whereNotNull('submitted')
            ->whereNull('user_feedback')->limit($limit)
            ->get();
        if ($completedExams->isEmpty()) {
            $this->info('No students completed exams since last check');
            return;
        }

        $userEmails = User::select('email')
            ->whereIn(
                'id',
                $completedExams->pluck('user_id')->all()
            )
            ->get()->pluck('email')->all();

        if (count($userEmails) > 0) {
            MailerHelper::sendTemplateEmail(templateName: AFTER_EXAM_SUBMISSION_EMAIL, emails: $userEmails, data: [], subject: '', bulk: true);
            user_exam::whereIn('id', $completedExams->pluck('id')->all())->update([
                'user_feedback' => Carbon::now()->toDateTimeString()
            ]);

            $this->info('Emails sent to students who completed exams since last check');
        } else {
            $this->info('No valid email addresses found.');
        }
    }
}
