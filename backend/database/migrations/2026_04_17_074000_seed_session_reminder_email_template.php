<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $content = <<<HTML
Hello {first_name},

This is a friendly reminder that your session for **{programme}** at **{centre}** starts in {days}.

**Session:** {session}
**Start Date:** {date}

Please make sure to arrive on time and come prepared.

We look forward to seeing you there!

Best regards,  
One Million Coders Team
HTML;

        DB::table('email_templates')->insertOrIgnore([
            'name' => 'SESSION_REMINDER_EMAIL',
            'content' => $content,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('email_templates')->where('name', 'SESSION_REMINDER_EMAIL')->delete();
    }
};
