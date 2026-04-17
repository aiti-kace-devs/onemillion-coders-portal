<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('sms_templates')->insertOrIgnore([
            'name' => 'SESSION_REMINDER_SMS',
            'content' => 'Hi {first_name}, your {programme} class at {centre} starts in {days} on {date}. See you!',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('sms_templates')->where('name', 'SESSION_REMINDER_SMS')->delete();
    }
};
