<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

include_once(app_path('/Helpers/Constants.php'));

/**
 * Remove the verification link/button from the OTP email template.
 *
 * OTP code entry in the registration form is now the sole verification
 * method — the email should only contain the code, not a clickable link.
 *
 * This migration updates the email_templates DB row to the link-free
 * version, replacing any previous content (including stale placeholders
 * like {instructionText}, {buttonText}, {verificationUrl}).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('email_templates')
            ->where('name', OTP_VERIFICATION_EMAIL)
            ->update([
                'content' => <<<'EOD'
## Verify Your Email

We received a request to verify your email address **{recipientEmail}** for programme registration.

<div style="text-align:center;background-color:#f8fafc;border:2px dashed #e2e8f0;border-radius:8px;padding:20px;margin:16px 0">
<p style="margin:0 0 6px 0;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:1.5px;font-weight:600">Your Verification Code</p>
<p style="margin:0;font-size:36px;font-weight:800;color:#2d3748;letter-spacing:8px;font-family:'Courier New',Courier,monospace">{otpCode}</p>
<p style="margin:8px 0 0 0;font-size:13px;color:#999">Expires in <strong>{expiresInMinutes} minutes</strong></p>
</div>

Enter this code in the registration form to verify your email address.

---

If you did not request this code, you can safely ignore this email. Never share your verification code with anyone.
EOD,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No rollback — the previous template content had stale/broken placeholders
    }
};
