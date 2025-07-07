<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;
use App\Jobs\SendSmsJob;

class SmsServiceProvider extends ServiceProvider
{


    public static function sendSMSService($phoneNumber, $smsMessage)
    {

    
        $phoneNumber = null;
        
        // Ensure SMS message is valid
        if (!$smsMessage) {
            return response()->json(['error' => 'SMS template is empty'], 500);
        }
    

    
        if (empty($smsMessage)) {
            return response()->json(['error' => 'Generated SMS message is empty'], 500);
        }
    
    
        // Dispatch SMS asynchronously
        dispatch(new SendSmsJob($phoneNumber, $smsMessage));
    
        return response()->json([
            'message' => 'SMS sent successfully',
            'smsMessage' => $smsMessage,
            'phoneNumber' => $phoneNumber
        ], 201);
    }

    
}
