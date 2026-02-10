<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendSmsJob;
use App\Mail\OtpVerificationMail;
use App\Rules\Recaptcha;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class OtpController extends Controller
{
    private OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Send an OTP code to the user's email address.
     *
     * The email always contains the OTP code directly (so the user can enter it
     * manually, e.g. if their phone is dead).
     *
     * If a phone number is also provided, clicking the verification link in the
     * email will additionally trigger an SMS with the SAME OTP code to that phone.
     *
     * POST /api/otp/send
     */
    public function send(Request $request): JsonResponse
    {
        // --- Validate input ---
        $validator = Validator::make($request->all(), [
            'email'           => 'required|email:rfc',
            'phone'           => 'nullable|string|min:8',
            'form_uuid'       => 'required|string|exists:forms,uuid',
            'recaptcha_token' => ['nullable', new Recaptcha('otp_request')],
        ], [
            'email.required'   => 'An email address is required to send the OTP.',
            'email.email'      => 'Please provide a valid email address.',
            'form_uuid.exists' => 'Invalid registration form reference.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $email = strtolower(trim($request->input('email')));
        $phone = $request->input('phone') ? trim($request->input('phone')) : null;

        // --- Rate-limit check ---
        $rateCheck = $this->otpService->canRequestOtp($email);
        if (!$rateCheck['allowed']) {
            return response()->json([
                'success'     => false,
                'message'     => $rateCheck['message'],
                'retry_after' => $rateCheck['retry_after'] ?? 60,
            ], 429);
        }

        // --- Generate and store OTP (hashed, scoped to this email) ---
        $otpCode = $this->otpService->generate();
        $this->otpService->store($email, $otpCode, $phone);

        // --- Build a signed verification URL (tamper-proof, expires with OTP) ---
        // The OTP is encrypted and embedded so that when the link is clicked,
        // the SAME code can be sent via SMS (not a freshly generated one).
        $verificationUrl = URL::temporarySignedRoute(
            'otp.verify.link',
            now()->addMinutes(10),
            [
                'email' => $email,
                'token' => Crypt::encryptString($otpCode),
            ]
        );

        // --- Send the email ---
        // The email always includes the OTP code directly so users with a dead
        // phone can still copy-paste the code into the registration form.
        $hasPhone = !empty($phone);

        try {
            Mail::to($email)->send(
                new OtpVerificationMail($otpCode, $verificationUrl, $email, 10, $hasPhone)
            );
        } catch (\Throwable $e) {
            Log::error('OTP email sending failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification email. Please try again.',
            ], 500);
        }

        $remainingTtl = $this->otpService->getRemainingTtl($email);

        return response()->json([
            'success'    => true,
            'message'    => 'Verification code sent to your email address.',
            'expires_in' => $remainingTtl,
            'has_phone'  => $hasPhone,
        ]);
    }

    /**
     * Verify the OTP code entered by the user.
     * The code is checked against the hash stored for THIS specific email —
     * a code generated for another email will NEVER match.
     *
     * POST /api/otp/verify
     */
    public function verify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ], [
            'otp.required' => 'Please enter the verification code.',
            'otp.size'     => 'The verification code must be exactly 6 digits.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $email = strtolower(trim($request->input('email')));
        $otp   = $request->input('otp');

        // Verify — scoped to this email, not interchangeable with other users
        $result = $this->otpService->verify($email, $otp);

        return response()->json([
            'success'            => $result['success'],
            'message'            => $result['message'],
            'verified'           => $result['success'],
            'remaining_attempts' => $result['remaining_attempts'] ?? null,
        ], $result['success'] ? 200 : 422);
    }

    /**
     * Verify via email link click (signed URL).
     *
     * When the user clicks the link in their email:
     *  1. The email address is marked as verified in Redis.
     *  2. If a phone number was associated, the SAME OTP code (decrypted from
     *     the URL token) is sent to that phone via SMS.
     *
     * GET /api/otp/verify-via-link (signed)
     */
    public function verifyViaLink(Request $request)
    {
        // Validate the signed URL — prevents tampering
        if (!$request->hasValidSignature()) {
            abort(403, 'This verification link is invalid or has expired.');
        }

        $email = strtolower(trim($request->query('email', '')));
        $encryptedToken = $request->query('token', '');

        if (empty($email)) {
            abort(400, 'Missing email parameter.');
        }

        // Mark as verified in Redis
        $this->otpService->markVerified($email);

        // Check if there's an associated phone number — if so, send the SAME OTP via SMS
        $smsSent = false;
        $phone = $this->otpService->getAssociatedPhone($email);

        if ($phone && $encryptedToken) {
            try {
                // Decrypt the SAME OTP that was in the email — no new code generated
                $otpCode = Crypt::decryptString($encryptedToken);

                $smsMessage = 'Your ' . config('app.name', 'One Million Coders')
                    . ' verification code is: ' . $otpCode
                    . '. This code expires in 10 minutes. Do not share it with anyone.';

                SendSmsJob::dispatch([$phone], $smsMessage);
                $smsSent = true;
            } catch (\Throwable $e) {
                Log::warning('OTP SMS dispatch failed after email link verification', [
                    'email' => $email,
                    'phone' => $phone,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Clean up the OTP code data (verified status remains)
        $this->otpService->invalidate($email);

        // Return a nice HTML page
        return response()->view('emails.otp-verified-success', [
            'email'   => $email,
            'smsSent' => $smsSent,
        ]);
    }

    /**
     * Check if an email has been OTP-verified (polling endpoint for frontend).
     *
     * GET /api/otp/status?email=...
     */
    public function status(Request $request): JsonResponse
    {
        $email = strtolower(trim($request->query('email', '')));

        if (empty($email)) {
            return response()->json(['success' => false, 'message' => 'Email is required.'], 422);
        }

        $verified = $this->otpService->isVerified($email);

        return response()->json([
            'success'  => true,
            'verified' => $verified,
        ]);
    }
}
