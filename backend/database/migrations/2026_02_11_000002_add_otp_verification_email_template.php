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
     * Email Template dashboard. Placeholders: otpCode,
     * recipientEmail, expiresInMinutes, appName.
     */
    public function up(): void
    {
        // updateOrInsert ensures the template content is always current:
        //  - Fresh install → inserts the row
        //  - Existing DB   → overwrites stale content (e.g. old MailEclipse
        //    [component]/[endcomponent] syntax that broke rendering)
        DB::table('email_templates')->updateOrInsert(
            ['name' => OTP_VERIFICATION_EMAIL],
            [
                'content' => self::templateContent(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * The canonical OTP email template content.
     *
     * IMPORTANT: This content is rendered inside <x-mail::message> via
     * GenericEmail's generic-email.blade.php, where Markdown::parse()
     * converts markdown to HTML. Raw HTML passes through unchanged.
     *
     * Do NOT use Blade component syntax here (<x-mail::button>, @component,
     * [component]: #) — the content is injected as a string, not compiled
     * by Blade, so component tags will appear as raw text in the email.
     *
     * Use raw HTML with inline styles for buttons and styled blocks.
     */
    private static function templateContent(): string
    {
        return <<<'EOD'
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
EOD;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('email_templates')->where('name', OTP_VERIFICATION_EMAIL)->delete();
    }
};
