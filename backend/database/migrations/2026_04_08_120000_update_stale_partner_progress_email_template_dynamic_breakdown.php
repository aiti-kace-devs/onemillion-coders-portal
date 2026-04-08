<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_templates')) {
            return;
        }

        $content = <<<'EOD'
## Hello {name},

This is a friendly reminder to continue your progress in **{course_name}**.

[component]: # ('mail::panel')
**Current progress:** {overall_progress}  <br>
{progress_breakdown}
<br>
Last activity: {last_activity_at}
[endcomponent]: #

Staying active helps you complete learning outcomes faster. Keep going.
EOD;

        DB::table('email_templates')
            ->where('name', PARTNER_PROGRESS_STALE_REMINDER_EMAIL)
            ->update([
                'content' => $content,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('email_templates')) {
            return;
        }

        $content = <<<'EOD'
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
EOD;

        DB::table('email_templates')
            ->where('name', PARTNER_PROGRESS_STALE_REMINDER_EMAIL)
            ->update([
                'content' => $content,
                'updated_at' => now(),
            ]);
    }
};
