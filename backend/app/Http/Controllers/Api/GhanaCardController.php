<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\VerifyGhanaCard;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GhanaCardController extends Controller
{
    /**
     * Submit Ghana Card for verification.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // 10MB max for upload, service will shrink it
            'pin' => 'sometimes|string|max:17', // Ghana Card PIN is 9 or 10 characters
        ]);

        $user = $request->user();

        // 1. Check if user is blocked from verification
        if ($user->is_verification_blocked) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is blocked from verification. Please contact the NIA office to resolve issues with your registration.',
            ], 403);
        }

        // 2. Check retry limit (failed attempts)
        $maxAttempts = config('GHANA_CARD_MAX_ATTEMPTS', 5);
        $failedAttemptsCount = \App\Models\GhanaCardVerification::where('user_id', $user->id)
            ->where('code', '!=', '00')
            ->count();

        if ($failedAttemptsCount >= $maxAttempts) {
            return response()->json([
                'success' => false,
                'message' => "You have reached the maximum number of failed verification attempts ({$maxAttempts}). Please contact support.",
            ], 403);
        }

        // 3. Ensure we have a PIN
        if (!$request->pin && !$user->ghcard) {
            return response()->json([
                'success' => false,
                'message' => 'No Ghana Card PIN found for this user. Please provide your PIN.',
            ], 422);
        }

        // 4. Update PIN if provided
        if ($request->pin) {
            $user->ghcard = $request->pin;
            $user->saveQuietly();
        }

        // 5. Store image temporarily for the queue
        $tempPath = 'temp_ghana_cards/' . Str::random(40) . '.' . $request->file('image')->getClientOriginalExtension();
        Storage::disk('private_cloud')->put($tempPath, file_get_contents($request->file('image')));

        // 6. Dispatch job
        VerifyGhanaCard::dispatch($user, $tempPath);

        return response()->json([
            'success' => true,
            'message' => 'Ghana Card verification has been queued. You will be notified once processed.',
        ]);
    }
}
