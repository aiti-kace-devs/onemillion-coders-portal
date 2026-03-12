<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $phone;
    protected string $message;

    protected $logger;

    public function __construct($phone, string $message)
    {
        $this->phone = is_array($phone) ? $phone : [$phone];
        $this->message = $message;
        $this->logger = app('SMSLogger');
    }

    public function handle(): void
    {
        $logOnly = (bool) config('sms.log_only', true);
        if ($logOnly) {
            $this->logger->info("Sending SMS message. [LogOnly: TRUE] ", ['phonenumber(s)' =>  implode(',', $this->phone), 'message' => $this->message]);
        } else {

            try {
                $response = Http::withHeaders([
                    'api-key' => config('services.arkesel.key'),
                ])->post('https://sms.arkesel.com/api/v2/sms/send', [
                    'sender' => substr(config('sms.sender'), 0, 11),
                    'message' => $this->message,
                    'recipients' => $this->phone,
                    'sandbox' => (bool) config('sms.use_sandbox', true),
                ]);
            } catch (\Throwable $e) {
            }
        }
    }
}
