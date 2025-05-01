<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

include_once(app_path('/Helpers/Constants.php'));

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('content');
            $table->timestamps();
        });

        DB::table('email_templates')->insert([
            [
                'name' => AFTER_REGISTRATION_EMAIL,
                'content' => <<<EOD
## Welcome, {name} !
We are excited to have you. Here are your participant login details:

[component]: # ('mail::panel')
**Email:** {email}   <br>
**Password:** {password}
[endcomponent]: #

You can log in and start your assessment test by clicking the button below:

[component]: # ('mail::button',  ['url' => '{examUrl}'])
Start Your Assessment Test
[endcomponent]: #


[component]: # ('mail::panel')
Please complete your assessment test as soon as possible.
If you are having trouble with the button copy and paste this URL in a browser: [{examUrl}]({examUrl})
[endcomponent]: #
EOD,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => AFTER_ADMISSION_EMAIL,
                'content' => <<<EOD
# Dear {name},
Congratulations on your selection as one of the shortlisted participants for **{course_name}**.

This is a final confirmation phase to ensure your availability for the training. Kindly take note of the following details:    <br>
**Start Date:** {start_date}    </br>
**Training Duration:** {duration}   </br>
**Venue:** {venue}   </br>
Kindly select a session that fits your schedule by clicking on the 'Select Session' button below.   <br>

[component]: # ('mail::button',  ['url' => '{url}'])
Select Session
[endcomponent]: #

[component]: # ('mail::panel')
Note: Only applicants who have selected their sessions will move to the next stage.
[endcomponent]: #
EOD,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => AFTER_ADMISSION_CONFIRMATION_EMAIL,
                'content' => <<<'EOD'
# Hello, {name}

This is to confirm that you have successfully enrolled for **{courseSessioName}**   <br>
Time is **{courseSessionTime}**

@if($data['link'])
Click on the link below to join the official Whatsapp group for the course
<x-mail::button url="{link}" color="success">Join WhatsApp Group</x-mail::button>
@endif
EOD,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => AFTER_EXAM_SUBMISSION_EMAIL,
                'content' => <<<EOD
Hi ,  <br>
We acknowledge your assessment test submission.
[component]: # ('mail::panel')
Please note that shortlisted applicants will be contacted as soon as possible.
[endcomponent]: #
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
        Schema::dropIfExists('email_templates');
    }
};
