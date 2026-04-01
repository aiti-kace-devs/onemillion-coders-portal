<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('email_templates')) {
            return;
        }

        $exists = DB::table('email_templates')
            ->where('name', PARTNER_PROGRESS_STALE_REMINDER_EMAIL)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('email_templates')->insert([
            'name' => PARTNER_PROGRESS_STALE_REMINDER_EMAIL,
            'content' => <<<'EOD'
## Hello {name},

This is a friendly reminder to continue your progress in **{course_name}**.

[component]: # ('mail::panel')
**Current progress:** {overall_progress}  <br>
Video: {video_progress}%  <br>
Quiz: {quiz_progress}%  <br>
Project: {project_progress}%  <br>
Task: {task_progress}%  <br>
Last activity: {last_activity_at}
[endcomponent]: #

Staying active helps you complete learning outcomes faster. Keep going.
EOD,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('email_templates')) {
            return;
        }

        DB::table('email_templates')
            ->where('name', PARTNER_PROGRESS_STALE_REMINDER_EMAIL)
            ->delete();
    }
};
