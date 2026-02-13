<?php

namespace App\Http\Controllers\Api;

use App\Helpers\MailerHelper;
use App\Http\Controllers\Controller;
use App\Jobs\SendSmsJob;
use App\Mail\OtpVerificationMail;
use App\Models\EmailTemplate;
use App\Models\User;
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
     * Real-time email availability check.
     *
     * The frontend calls this (debounced) as the user types their email to provide
     * instant feedback. No OTP is sent — this is a lightweight read-only check.
     *
     * Returns:
     *  - { available: true }                                  → email is free
     *  - { available: false, reason: "registered" }           → already in users table
     *  - { available: false, reason: "otp_active" }           → another user has an active OTP flow
     *
     * GET /api/otp/check-email?email=...
     */
    public function checkEmail(Request $request): JsonResponse
    {
        $email = strtolower(trim($request->query('email', '')));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success'   => false,
                'available' => false,
                'message'   => 'A valid email address is required.',
            ], 422);
        }

        $result = $this->otpService->checkEmailAvailability($email);

        $messages = [
            'registered' => 'This email address is already registered. Please use a different email or log in to your existing account.',
            'used'       => 'This email address has already been used for registration. Please use a different email.',
            'otp_active' => 'This email address is currently in a verification process. Please try again later or use a different email.',
        ];

        return response()->json([
            'success'   => true,
            'available' => $result['available'],
            'reason'    => $result['reason'] ?? null,
            'message'   => $result['available'] ? 'Email is available.' : ($messages[$result['reason']] ?? 'Email is not available.'),
        ]);
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
     * Security checks before sending:
     *  1. Email not already registered (users table)
     *  2. Email already verified (not yet consumed) — returns already_verified
     *     instead of overwriting the existing verification with a new OTP
     *  3. Rate-limit not exceeded
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

        // --- Check 1: Email already registered ---
        if (User::where('email', $email)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This email address is already registered. Please use a different email or log in to your existing account.',
                'errors'  => [
                    'email' => ['This email address is already registered.'],
                ],
            ], 409);
        }

        // --- Check 2: Already verified (not yet consumed) ---
        // If the email is already OTP-verified and the verification hasn't been
        // consumed by a registration, there's no reason to send a new OTP.
        //
        // This prevents a CRITICAL bug: store() overwrites verified_at with NULL,
        // which would invalidate a previous successful verification.
        //
        // Returning success + already_verified = true lets the frontend immediately
        // mark the email as verified without the user having to re-enter a code.
        if ($this->otpService->isVerified($email)) {
            return response()->json([
                'success'          => true,
                'already_verified' => true,
                'message'          => 'This email has already been verified. You can proceed with registration.',
            ]);
        }

        // --- Check 3: Rate-limit ---
        $rateCheck = $this->otpService->canRequestOtp($email);
        if (!$rateCheck['allowed']) {
            return response()->json([
                'success'     => false,
                'message'     => $rateCheck['message'],
                'retry_after' => $rateCheck['retry_after'] ?? 60,
            ], 429);
        }

        // --- Generate and store OTP (hashed, scoped to this email) ---
        // This also creates/updates the persistent lifecycle row in otp_verified_emails
        $otpCode = $this->otpService->generate();
        $this->otpService->store($email, $otpCode, $phone);

        $otpMinutes = $this->otpService->otpTtlMinutes();

        // --- Build a signed verification URL (tamper-proof, expires with OTP) ---
        $verificationUrl = URL::temporarySignedRoute(
            'otp.verify.link',
            now()->addMinutes($otpMinutes),
            [
                'email' => $email,
                'token' => Crypt::encryptString($otpCode),
            ]
        );

        $hasPhone = !empty($phone);
        $appName = config('app.name', 'One Million Coders');

        // --- Send the email ---
        // Prefer admin-configured template (OTP_VERIFICATION_EMAIL) if exists; else use OtpVerificationMail.
        try {
            $template = EmailTemplate::where('name', OTP_VERIFICATION_EMAIL)->first();
            if ($template) {
                $instructionText = $hasPhone
                    ? 'Click the button below to verify your email and receive this same code via SMS on your phone. If your phone is unavailable, enter the code above in the registration form.'
                    : 'Enter this code in the registration form, or click the button below to verify instantly.';
                $buttonText = $hasPhone ? 'Verify Email & Send SMS' : 'Verify My Email';

                $sent = MailerHelper::sendTemplateEmail(
                    OTP_VERIFICATION_EMAIL,
                    $email,
                    [
                        'otpCode'          => $otpCode,
                        'verificationUrl'  => $verificationUrl,
                        'recipientEmail'   => $email,
                        'expiresInMinutes' => $otpMinutes,
                        'appName'          => $appName,
                        'instructionText'  => $instructionText,
                        'buttonText'       => $buttonText,
                    ],
                    "Your Verification Code — {$appName}",
                    false
                );
                if (!$sent) {
                    throw new \RuntimeException('Template email send returned false');
                }
            } else {
                Mail::to($email)->send(
                    new OtpVerificationMail($otpCode, $verificationUrl, $email, $otpMinutes, $hasPhone)
                );
            }
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
     *  1. The email address is marked as verified in the persistent database
     *     (otp_verified_emails table).
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

        // Mark as verified in persistent database (otp_verified_emails table).
        // If no lifecycle row exists (OTP was never sent), abort — the link is invalid.
        if (!$this->otpService->markVerified($email)) {
            abort(400, 'Verification failed. No active OTP session was found for this email.');
        }

        // Check if there's an associated phone number — if so, send the SAME OTP via SMS
        $smsSent = false;
        $phone = $this->otpService->getAssociatedPhone($email);

        if ($phone && $encryptedToken) {
            try {
                // Decrypt the SAME OTP that was in the email — no new code generated
                $otpCode = Crypt::decryptString($encryptedToken);

                $otpMinutes = $this->otpService->otpTtlMinutes();
                $smsMessage = 'Your ' . config('app.name', 'One Million Coders')
                    . ' verification code is: ' . $otpCode
                    . '. This code expires in ' . $otpMinutes . ' minutes. Do not share it with anyone.';

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
