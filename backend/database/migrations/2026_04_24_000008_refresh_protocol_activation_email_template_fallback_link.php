<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

include_once(app_path('/Helpers/Constants.php'));

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $template = DB::table('email_templates')
            ->where('name', PROTOCOL_ACTIVATION_INVITATION_EMAIL)
            ->value('content');

        if (! is_string($template) || trim($template) === '') {
            return;
        }

        $updated = str_replace(
            "If the button does not work, copy and open this URL in your browser:\n\n[{activationUrl}]({activationUrl})",
            "If the button does not work, use this secure fallback link instead:\n\n[Open your account activation link]({activationUrl})\n\nInvite reference: **{activationInviteCode}**",
            $template
        );

        if ($updated !== $template) {
            DB::table('email_templates')
                ->where('name', PROTOCOL_ACTIVATION_INVITATION_EMAIL)
                ->update([
                    'content' => $updated,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $template = DB::table('email_templates')
            ->where('name', PROTOCOL_ACTIVATION_INVITATION_EMAIL)
            ->value('content');

        if (! is_string($template) || trim($template) === '') {
            return;
        }

        $updated = str_replace(
            "If the button does not work, use this secure fallback link instead:\n\n[Open your account activation link]({activationUrl})\n\nInvite reference: **{activationInviteCode}**",
            "If the button does not work, copy and open this URL in your browser:\n\n[{activationUrl}]({activationUrl})",
            $template
        );

        if ($updated !== $template) {
            DB::table('email_templates')
                ->where('name', PROTOCOL_ACTIVATION_INVITATION_EMAIL)
                ->update([
                    'content' => $updated,
                    'updated_at' => now(),
                ]);
        }
    }
};
