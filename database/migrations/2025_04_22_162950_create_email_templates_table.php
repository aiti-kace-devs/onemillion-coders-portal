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
                'name' => AFTER_ADMISSION_EMAIL,
                'content' => "# Dear {name}, \n\nCongratulations on your selection as one of the shortlisted participants for <b>{course_name}</b>.
                                This is a final confirmation phase to ensure your availability for the training. Kindly take note of the following details:
                                 \n\nStart Date: {start_date} \nTraining Duration: {duration} \nVenue:: {venue} \nKindly select a session that fits your schedule by clicking on the 'Select Session' button below.",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => AFTER_ADMISSION_CONFIRMATION_EMAIL,
                'content' => "Hello, {name}, \n\nThis is to confirm that you have successfully enrolled for {session_name}. Time is {course_time}",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => AFTER_EXAM_SUBMISSION_EMAIL,
                'content' => "Hi , \nYWe acknowledge your assessment test submission. Please note that shortlisted applicants will be contacted by the end of April. \n\nThank you. ",
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
