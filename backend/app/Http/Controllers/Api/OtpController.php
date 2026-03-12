<?php

namespace App\Http\Controllers\Api;

use App\Helpers\MailerHelper;
use App\Http\Controllers\Controller;
use App\Mail\OtpVerificationMail;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Rules\Recaptcha;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
     * The email contains the OTP code which the user enters in the
     * registration form to prove ownership of the email address.
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
        $this->otpService->store($email, $otpCode);

        $otpMinutes = $this->otpService->otpTtlMinutes();
        $appName = config('app.name', 'One Million Coders');

        // --- Send the email ---
        // Extend execution time for the SMTP send. Gmail SMTP can be slow
        // (30-60 s) under load, and the default max_execution_time (30 s)
        // causes a fatal PHP error that crashes artisan serve.
        $previousTimeLimit = ini_get('max_execution_time');
        set_time_limit(120);

        // Prefer admin-configured template (OTP_VERIFICATION_EMAIL) if exists; else use OtpVerificationMail.
        try {
            $template = EmailTemplate::where('name', OTP_VERIFICATION_EMAIL)->first();
            if ($template) {
                $sent = MailerHelper::sendTemplateEmail(
                    OTP_VERIFICATION_EMAIL,
                    $email,
                    [
                        'otpCode'          => $otpCode,
                        'recipientEmail'   => $email,
                        'expiresInMinutes' => $otpMinutes,
                        'appName'          => $appName,
                    ],
                    "Your Verification Code — {$appName}",
                    false
                );
                if (!$sent) {
                    throw new \RuntimeException('Template email send returned false');
                }
            } else {
                Mail::to($email)->send(
                    new OtpVerificationMail($otpCode, $email, $otpMinutes)
                );
            }
        } catch (\Throwable $e) {
            set_time_limit((int) $previousTimeLimit ?: 30);

            Log::error('OTP email sending failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification email. Please try again.',
            ], 500);
        }

        set_time_limit((int) $previousTimeLimit ?: 30);

        $remainingTtl = $this->otpService->getRemainingTtl($email);

        return response()->json([
            'success'    => true,
            'message'    => 'Verification code sent to your email address.',
            'expires_in' => $remainingTtl,
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
     * Check if an email has been OTP-verified.
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
