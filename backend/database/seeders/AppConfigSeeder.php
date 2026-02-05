<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ✓ DEFINE CONSTANTS HERE (in seeder)
        // This ensures they exist before we use them
        defined('EXAM_DEADLINE_AFTER_REGISTRATION') or define('EXAM_DEADLINE_AFTER_REGISTRATION', 'EXAM_DEADLINE_AFTER_REGISTRATION');
        defined('ALLOW_COURSE_CHANGE') or define('ALLOW_COURSE_CHANGE', 'ALLOW_COURSE_CHANGE');
        defined('ALLOW_SESSION_CHANGE') or define('ALLOW_SESSION_CHANGE', 'ALLOW_SESSION_CHANGE');
        defined('SEND_EMAIL_AFTER_REGISTRATION') or define('SEND_EMAIL_AFTER_REGISTRATION', 'SEND_EMAIL_AFTER_REGISTRATION');
        defined('SEND_SMS_AFTER_REGISTRATION') or define('SEND_SMS_AFTER_REGISTRATION', 'SEND_SMS_AFTER_REGISTRATION');
        defined('SEND_EMAIL_AFTER_EXAM_SUBMISSION') or define('SEND_EMAIL_AFTER_EXAM_SUBMISSION', 'SEND_EMAIL_AFTER_EXAM_SUBMISSION');
        defined('SEND_SMS_AFTER_EXAM_SUBMISSION') or define('SEND_SMS_AFTER_EXAM_SUBMISSION', 'SEND_SMS_AFTER_EXAM_SUBMISSION');
        defined('SEND_EMAIL_AFTER_ADMISSION_CREATION') or define('SEND_EMAIL_AFTER_ADMISSION_CREATION', 'SEND_EMAIL_AFTER_ADMISSION_CREATION');
        defined('SEND_SMS_AFTER_ADMISSION_CREATION') or define('SEND_SMS_AFTER_ADMISSION_CREATION', 'SEND_SMS_AFTER_ADMISSION_CREATION');
        defined('SEND_EMAIL_AFTER_ADMISSION_CONFIRMATION') or define('SEND_EMAIL_AFTER_ADMISSION_CONFIRMATION', 'SEND_EMAIL_AFTER_ADMISSION_CONFIRMATION');
        defined('SEND_SMS_AFTER_ADMISSION_CONFIRMATION') or define('SEND_SMS_AFTER_ADMISSION_CONFIRMATION', 'SEND_SMS_AFTER_ADMISSION_CONFIRMATION');

        // Now use them
        try {
            DB::table('app_configs')->insert([
                [
                    'key' => EXAM_DEADLINE_AFTER_REGISTRATION,
                    'value' => 7,
                    'type' => 'integer',
                    'is_cached' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => ALLOW_COURSE_CHANGE,
                    'value' => 1,
                    'type' => 'boolean',
                    'is_cached' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => ALLOW_SESSION_CHANGE,
                    'value' => 1,
                    'type' => 'boolean',
                    'is_cached' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => SEND_EMAIL_AFTER_REGISTRATION,
                    'value' => 1,
                    'type' => 'boolean',
                    'is_cached' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => SEND_SMS_AFTER_REGISTRATION,
                    'value' => 0,
                    'type' => 'boolean',
                    'is_cached' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => SEND_EMAIL_AFTER_EXAM_SUBMISSION,
                    'value' => 1,
                    'type' => 'boolean',
                    'is_cached' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => SEND_SMS_AFTER_EXAM_SUBMISSION,
                    'value' => 0,
                    'type' => 'boolean',
                    'is_cached' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => SEND_EMAIL_AFTER_ADMISSION_CREATION,
                    'value' => 1,
                    'type' => 'boolean',
                    'is_cached' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => SEND_SMS_AFTER_ADMISSION_CREATION,
                    'value' => 1,
                    'type' => 'boolean',
                    'is_cached' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => SEND_EMAIL_AFTER_ADMISSION_CONFIRMATION,
                    'value' => 1,
                    'type' => 'boolean',
                    'is_cached' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => SEND_SMS_AFTER_ADMISSION_CONFIRMATION,
                    'value' => 1,
                    'type' => 'boolean',
                    'is_cached' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}