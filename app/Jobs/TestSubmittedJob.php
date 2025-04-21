<?php

namespace App\Jobs;

use App\Helpers\SmsHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TestSubmittedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public $student, public $result)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!config(SEND_SMS_AFTER_EXAM_SUBMISSION, true)) return;
        $smsContent = SmsHelper::getTemplate(AFTER_EXAM_SUBMISSION_SMS, [
            'name' => $this->student['name'],
        ]) ?? '';
        $details['message'] = $smsContent;
        $details['phonenumber'] = $this->student['mobile_no'];

        SendSMSAfterRegistrationJob::dispatch($details);
    }
}
