<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $phone;
    protected $message;
    protected $logger;

    public function __construct($phone, $message)
    {
        $this->phone =  is_array($phone) ? $phone : [$phone];
        $this->message = $message;
        $this->logger = app('SMSLogger');
    }



    public function handle()
    {
        $LOGONLY = env('LOG_SMS_ONLY', app()->isLocal() ? true : false);

        if ($LOGONLY) {
            $this->logger->info("Sending SMS message. [LogOnly: TRUE] ", ['phonenumber(s)' =>  implode(',', $this->phone), 'message' => $this->message]);
            return;
        }

        try {
            $apiKey = env('ARKESEL_SMS_API_KEY');

            $sender = substr(env('SMS_SENDER_NAME', '1M-CODERS'), 0, 11);

            $response = Http::withHeaders([
                'api-key' => $apiKey
            ])->post('https://sms.arkesel.com/api/v2/sms/send', [
                'sender' => $sender,
                'message' => $this->message,
                'recipients' => $this->phone,
                'sandbox' => env('USE_SMS_SANDBOX', config('app.env') === 'local' ? true : false),
            ]);

            $this->logger->info('SMS Sent Successfully ', ['response' => $response->json()]);
            return $response->json();
        } catch (\Exception $e) {
            $this->logger->error('Failed to send SMS ', ['phonenumber(s) ' =>  implode(', ', $this->phone), 'message' => $this->message, 'error' => $e->getMessage()]);
            Log::error('SMS Job Failed', ['error' => $e->getMessage()]);
        }
    }
}
