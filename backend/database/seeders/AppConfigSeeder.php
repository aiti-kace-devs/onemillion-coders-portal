<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            DB::table('app_configs')->insertOrIgnore([
                [
                    'key' => EXAM_DEADLINE_AFTER_REGISTRATION,
                    'value' => 7,
                    'type' => 'integer',
                    'is_cached' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => DEFAULT_PROTOCOL_RESERVED_LONG_SLOTS,
                    'value' => 1,
                    'type' => 'integer',
                    'is_cached' => true,
                    'created_at' => now(),
                    'updated_at' => now(),

                ],
                [
                    'key' => DEFAULT_PROTOCOL_RESERVED_SHORT_SLOTS,
                    'value' => 2,
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
                [
                    'key' => SHOW_RESULTS_TO_STUDENTS,
                    'value' => 1,
                    'type' => 'boolean',
                    'is_cached' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => SHOW_STUDENT_LEVEL,
                    'value' => 0,
                    'type' => 'boolean',
                    'is_cached' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => SHOW_COURSE_ASSESSMENT_TO_STUDENTS,
                    'value' => 1,
                    'type' => 'boolean',
                    'is_cached' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                // OTP verification parameters (admin-configurable)
                ['key' => 'OTP_TTL', 'value' => 600, 'type' => 'integer', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
                ['key' => 'OTP_VERIFIED_TTL', 'value' => 1800, 'type' => 'integer', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
                ['key' => 'OTP_MAX_REQUESTS', 'value' => 3, 'type' => 'integer', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
                ['key' => 'OTP_REQUEST_WINDOW', 'value' => 600, 'type' => 'integer', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
                ['key' => 'OTP_MAX_ATTEMPTS', 'value' => 5, 'type' => 'integer', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],

                // Tiered Assessment parameters
                ['key' => ASSESSMENT_MAX_QUESTIONS, 'value' => 10, 'type' => 'integer', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
                ['key' => ASSESSMENT_PASSING_SCORE, 'value' => 8, 'type' => 'integer', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
                ['key' => ASSESSMENT_LEVEL_TIMEOUT_SECONDS, 'value' => 900, 'type' => 'integer', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],

                // Ghana Card Verification
                ['key' => GHANA_CARD_MAX_ATTEMPTS, 'value' => 5, 'type' => 'integer', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
                ['key' => VERIFICATION_PROFILE_IMAGE_UPLOAD_URL, 'value' => '', 'type' => 'string', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
                ['key' => VERIFICATION_PROFILE_IMAGE_STORAGE_DISK, 'value' => 'private_cloud', 'type' => 'string', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
                ['key' => VERIFICATION_PROFILE_IMAGE_STORAGE_DIR, 'value' => 'omcp/users-profile', 'type' => 'string', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
                ['key' => VERIFICATION_PROFILE_IMAGE_TIMEOUT_SECONDS, 'value' => 15, 'type' => 'integer', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
                ['key' => VERIFICATION_PROFILE_IMAGE_ENABLED, 'value' => 1, 'type' => 'boolean', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
                // Booking System parameters
                ['key' => SHORT_SLOTS_PERCENTAGE, 'value' => 60, 'type' => 'integer', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
                ['key' => LONG_SLOTS_PERCENTAGE, 'value' => 40, 'type' => 'integer', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
                ['key' => WAITLIST_NOTIFY_LIMIT, 'value' => 5, 'type' => 'integer', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
                ['key' => AVAILABILITY_CACHE_TTL, 'value' => 300, 'type' => 'integer', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
                ['key' => APPLICATION_REVIEW_IFRAME_URL, 'value' => '', 'type' => 'string', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],

                // Session Reminder settings
                ['key' => ONE_WEEK_REMINDER, 'value' => 1, 'type' => 'boolean', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
                ['key' => THREE_DAYS_REMINDER, 'value' => 1, 'type' => 'boolean', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
                ['key' => ONE_DAY_REMINDER, 'value' => 1, 'type' => 'boolean', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],

                // Admission Revocation Cooldown
                ['key' => ADMISSION_REVOCATION_COOLDOWN_HOURS, 'value' => 24, 'type' => 'integer', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
