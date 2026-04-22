<?php

namespace App\Jobs;

use App\Helpers\MailerHelper;
use App\Http\Controllers\NotificationController;
use App\Models\Oex_exam_master;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendExamLoginCredentialsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;
    public string $plainPassword;

    protected ?User $std = null;

    public function __construct(int $userId, string $plainPassword)
    {
        $this->userId = $userId;
        $this->plainPassword = $plainPassword;
    }

    public function handle()
    {
        $this->std = User::find($this->userId);

        if (!$this->std) {
            return;
        }

        // $deadline = $this->exam_deadline();

        $fullName = trim(
            ($this->std->first_name ?? '') . ' ' .
            ($this->std->middle_name ?? '') . ' ' .
            ($this->std->last_name ?? '')
        );

        $fullName = preg_replace('/\s+/', ' ', $fullName);
        $appName = config('app.name', 'One Million Coders');

        MailerHelper::sendTemplateEmail(
            AFTER_REGISTRATION_EMAIL,
            $this->std->email,
            [
                'name' => $fullName,
                // 'deadline' => $deadline,
                'password' => $this->plainPassword,
                'email' => $this->std->email,
                'examUrl' => url('/student/level-assessment'),
            ],
            "Welcome — {$appName}",
            false,
            false
        );

        NotificationController::notify(
            $this->std->id,
            'WELCOME',
            "Welcome to {$appName}!",
            "Congratulations, {$fullName}! Your account has been successfully created. "
            . "We're thrilled to have you join us — you're taking a great step forward. "
            . "Your next step is to review the application process. "
            . "Head to <strong>Application Review</strong> on your dashboard to get started."
        );
    }

    // NO MORE EXAMS 14/03/2026
    // private function exam_deadline(): string
    // {
    //     $registered = $this->std->created_at;
    //     $now = Carbon::now();

    //     $exam = Oex_exam_master::find($this->std->exam);
    //     $date = $exam?->exam_date ?? now();

    //     $studentDeadline = $registered
    //         ->addDays(config('EXAM_DEADLINE_AFTER_REGISTRATION', 2));

    //     $hoursLeft = $now->diffInHours($studentDeadline);
    //     $daysLeft  = $now->diffInDays($studentDeadline);

    //     $deadlineText = $daysLeft > 3
    //         ? "$daysLeft days"
    //         : "$hoursLeft hour(s)";

    //     return $studentDeadline->toDateString() . " in $deadlineText";
    // }
}
