<?php

namespace App\Jobs;

use App\Helpers\MailerHelper;
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
            Log::error('SendExamLoginCredentialsJob: User not found', [
                'user_id' => $this->userId
            ]);
            return;
        }

        Log::info('SendExamLoginCredentialsJob triggered', [
            'user_id' => $this->std->id
        ]);

        $deadline = $this->exam_deadline();

        $fullName = trim(
            ($this->std->first_name ?? '') . ' ' .
            ($this->std->middle_name ?? '') . ' ' .
            ($this->std->last_name ?? '')
        );

        $fullName = preg_replace('/\s+/', ' ', $fullName);

        MailerHelper::sendTemplateEmail(
            AFTER_REGISTRATION_EMAIL,
            $this->std->email,
            [
                'name'     => $fullName,
                'deadline' => $deadline,
                'password' => $this->plainPassword,
                'email'    => $this->std->email,
                'examUrl'  => url('/student/exam'),
            ],
            'One Million Coders Login Credentials'
        );

    }

    private function exam_deadline(): string
    {
        $registered = $this->std->created_at;
        $now = Carbon::now();

        $exam = Oex_exam_master::find($this->std->exam);
        $date = $exam?->exam_date ?? $now;

        $studentDeadline = (new Carbon($registered))
            ->addDays(config('EXAM_DEADLINE_AFTER_REGISTRATION', 2));

        $hoursLeft = $now->diffInHours($studentDeadline);
        $daysLeft  = $now->diffInDays($studentDeadline);

        $deadlineText = $daysLeft > 3
            ? "$daysLeft days"
            : "$hoursLeft hour(s)";

        return $studentDeadline->toDateString() . " in $deadlineText";
    }
}
