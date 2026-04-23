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
        DB::table('email_templates')
            ->where('name', AFTER_ADMISSION_CONFIRMATION_EMAIL)
            ->update([
                'content' => <<<'EOD'
# Dear {name},

Congratulations on your successful enrollment in **{course_name}**.

This is to confirm your training details for **{courseSessioName}**. <br>
**Time:** {courseSessionTime} <br>
**Start Date:** {start_date} <br>
**End Date:** {end_date} <br>
**Training Duration:** {duration} <br>
**Venue:** {venue} <br>
**Learning Mode:** {support_mode} <br>
**Student ID:** {student_id}

[component]: # ('mail::panel')
Please keep this information for your records and ensure you are available to participate as scheduled.
[endcomponent]: #

@if($data['link'])
You can join the official WhatsApp group for this session by clicking the button below.

[component]: # ('mail::button', ['url' => '{link}'])
Join WhatsApp Group
[endcomponent]: #
@endif

[component]: # ('mail::panel')
If any of the details above change, you will be notified through your registered email address and phone number.
[endcomponent]: #
EOD,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('email_templates')
            ->where('name', AFTER_ADMISSION_CONFIRMATION_EMAIL)
            ->update([
                'content' => <<<'EOD'
# Hello, {name}

This is to confirm that you have successfully enrolled for **{courseSessioName}**   <br>
Time is **{courseSessionTime}**

@if($data['link'])
Click on the link below to join the official Whatsapp group for the course
<x-mail::button url="{link}" color="success">Join WhatsApp Group</x-mail::button>
@endif
EOD,
                'updated_at' => now(),
            ]);
    }
};
