<?php

use App\Models\SmsTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

include_once(app_path('/Helpers/Constants.php'));

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('content');
            $table->timestamps();
        });

        DB::table('sms_templates')->insert([
            [
                'name' => AFTER_REGISTRATION_SMS,
                'content' => "Hi {name}, \nYour OneMillionCoder registration was successful. Please check your email for instructions on taking the aptitude test. Good luck!",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => AFTER_EXAM_SUBMISSION_SMS,
                'content' => "Hi {name}, \nWe acknowledge your aptitude test submission. We will get back you later.",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => AFTER_ADMISSION_SMS,
                'content' => "Hi {name}, \nYour admission was successful. Please check your email for more details.",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => AFTER_ADMISSION_CONFIRMATION_SMS,
                'content' => "Congratulations {name}, \nYou have successfully confirmed your admission for {course}.",
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
        Schema::dropIfExists('sms_templates');
    }
};
