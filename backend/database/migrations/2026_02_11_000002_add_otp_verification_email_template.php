<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

include_once(app_path('/Helpers/Constants.php'));

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds OTP verification email template. Admins can edit content via
     * Email Template dashboard. Placeholders: otpCode, verificationUrl,
     * recipientEmail, expiresInMinutes, hasPhone, appName.
     */
    public function up(): void
    {
        DB::table('email_templates')->insertOrIgnore([
            [
                'name' => OTP_VERIFICATION_EMAIL,
                'content' => <<<'EOD'
## Verify Your Email

We received a request to verify your email address **{recipientEmail}** for programme registration.

### Your Verification Code

**{otpCode}**

Expires in **{expiresInMinutes}** minutes.

---

{instructionText}

<div style="text-align:center;margin:28px 0">
<a href="{verificationUrl}" target="_blank" style="display:inline-block;padding:14px 36px;background:linear-gradient(135deg,#FCD116,#e6b800);color:#1a1a2e;border-radius:10px;text-decoration:none;font-weight:700;font-size:15px;letter-spacing:0.3px">{buttonText}</a>
</div>

---

If you did not request this code, you can safely ignore this email. Never share your verification code with anyone.
EOD,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('email_templates')->where('name', OTP_VERIFICATION_EMAIL)->delete();
    }
};
