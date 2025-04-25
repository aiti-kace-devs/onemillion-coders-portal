<?php

namespace App\Jobs;

use App\Helpers\SmsHelper;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendBulkSMSJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $student_ids;
    public $message;
    public $list;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $data)
    {
        $this->student_ids = $data['student_ids'] ?? null;
        $this->message = $data['message'] ?? null;
        $this->list = $data['list'] ?? null;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        // Determine whether we need to send one message or multiple
        $messageContainsCurlyBrackets = isset($this->message) ? Str::containsAll($this->message, ['{', '}']) : false;

        if ($this->list) {
            $this->handleListSMS();
            return;
        }

        if ($this->student_ids) {
            collect($this->student_ids)->chunk(500)->each(function ($ids) use ($messageContainsCurlyBrackets) {
                $this->processChunk($ids, $messageContainsCurlyBrackets);
            });
        }
    }

    /**
     * Replace placeholders in message using user attributes.
     */
    private function replacePlaceholders(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $template = str_replace('{' . $key . '}', $value ?? '', $template);
        }
        return $template;
    }

    /**
     * Send SMS via Arkesel API.
     */
    private function sendSMS(array $phone, string $message): void
    {
        try {
            $apiKey = env('ARKESEL_SMS_API_KEY');
            $sender = substr(env('SMS_SENDER_NAME', 'ApTest'), 0, 11);

            $response = Http::withHeaders([
                'api-key' => $apiKey,
            ])->post('https://sms.arkesel.com/api/v2/sms/send', [
                'sender' => $sender,
                'message' => $message,
                'recipients' => $phone,
                'sandbox' => env('USE_SMS_SANDBOX', config('app.env') === 'local' ? true : false),

            ]);
            $phoneNumbers = implode('|', $phone);
            Log::info("SMS sent to {$phoneNumbers}", [
                'response' => $response->json()
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send SMS to {$phoneNumbers}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle email sending for list_name case
     */
    protected function handleListSMS(): void
    {
        // Fetch data from the database view
        $recipients = DB::table($this->list)->get();
        // Process in chunks to avoid memory issues
        $recipients->chunk(500)->each(function ($chunk) {
            // Check if message contains variables
            $messageContainsVariables = Str::containsAll($this->message, ['{', '}']);

            if ($messageContainsVariables) {
                foreach ($chunk as $recipient) {
                    $message = SmsHelper::replaceVariables($this->message, (array)$recipient);
                    $this->sendSMS([$this->getRecipientNumber($recipient)], $message);
                }
            } else {
                $numbers = $chunk->pluck('mobile_no')->all();
                $noNumber = $numbers[0] == '';
                if ($noNumber) {
                    $numbers = $chunk->map(function ($recipient) {
                        return $this->getRecipientNumber($recipient);
                    })->all();
                }
                $this->sendSMS($numbers, $this->message);
            }
        });
    }

    /**
     * Process a chunk of student IDs
     */
    protected function processChunk($ids, $messageContainsCurlyBrackets): void
    {
        if ($messageContainsCurlyBrackets) {
            $chunkedUsers = User::whereIn('id', $ids)->get()->all();
            foreach ($chunkedUsers as $user) {
                $message = SmsHelper::replaceVariables($this->message, $user->toArray());
                $this->sendSMS([$user->mobile_no], $message);
            }
        } else {
            $numbers = User::whereIn('id', $ids)->select('mobile_no')->pluck('mobile_no')->all();
            $this->sendSMS($numbers, $this->message);
        }
    }

    private function getRecipientNumber($recipient)
    {
        return $recipient->mobile_no ?? $recipient->phonenumber ?? $recipient->phone_number ?? '';
    }
}
