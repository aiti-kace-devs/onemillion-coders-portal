<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleSheets
{
    public static function updateGoogleSheets($userId, array $dataToSend)
    {
        // return;
        $apiEndpoint = env('SheetUpdateURL');
        $data = [
            'sheetIndex' =>  env('SHEET_INDEX', 0),
            'userId' => $userId,
            'data' => $dataToSend,
        ];

        if (isset($dataToSend['sheetTitle'])) {
            $data['sheetTitle'] = $dataToSend['sheetTitle'];
            $data['sheetIndex'] = null;
        }

        $response = Http::post($apiEndpoint, $data);

        if ($response->successful()) {
            return response()->json(['status' => 'success', 'message' => 'Update sent successfully.']);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Failed to send result.'], $response->status());
        }
    }
}
