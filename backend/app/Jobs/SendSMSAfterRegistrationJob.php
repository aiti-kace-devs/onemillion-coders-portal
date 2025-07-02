<?php

namespace App\Jobs;

use App\Models\FormResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSMSAfterRegistrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $details;
    /**
     * Create a new job instance.
     */
    public function __construct(array $details)
    {
        $this->details = $details;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (isset($this->details['message']) && isset($this->details['phonenumber']) && $this->details['message'] != '' && $this->details['phonenumber'] != '') {
            SendSmsJob::dispatch($this->details['phonenumber'], $this->details['message']);
        }
    }
}
