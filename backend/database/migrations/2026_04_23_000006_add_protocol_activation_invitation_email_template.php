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
        DB::table('email_templates')->updateOrInsert(
            ['name' => PROTOCOL_ACTIVATION_INVITATION_EMAIL],
            [
                'content' => <<<'EOD'
## Welcome, {displayName}

You have been pre-listed for the **One Million Coders Programme**.

Please activate your account with the secure link below. The link is single-use, and you will still be asked to confirm your Ghana Card number before activation is completed.

[component]: # ('mail::button', ['url' => '{activationUrl}'])
Activate My Account
[endcomponent]: #

[component]: # ('mail::panel')
**National ID:** {ghcard}<br>
**Email:** {email}
[endcomponent]: #

If the button does not work, use this secure fallback link instead:

[Open your account activation link]({activationUrl})

Invite reference: **{activationInviteCode}**
EOD,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('email_templates')
            ->where('name', PROTOCOL_ACTIVATION_INVITATION_EMAIL)
            ->delete();
    }
};
