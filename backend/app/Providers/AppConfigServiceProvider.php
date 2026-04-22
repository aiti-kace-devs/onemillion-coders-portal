<?php

namespace App\Providers;

use App\Models\AppConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class AppConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Define PHP constants for configuration keys
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
        defined('SHOW_RESULTS_TO_STUDENTS') or define('SHOW_RESULTS_TO_STUDENTS', 'SHOW_RESULTS_TO_STUDENTS');
        defined('SHOW_STUDENT_LEVEL') or define('SHOW_STUDENT_LEVEL', 'SHOW_STUDENT_LEVEL');
        defined('SHOW_COURSE_ASSESSMENT_TO_STUDENTS') or define('SHOW_COURSE_ASSESSMENT_TO_STUDENTS', 'SHOW_COURSE_ASSESSMENT_TO_STUDENTS');

        // OTP verification settings (admin-configurable)
        defined('OTP_TTL') or define('OTP_TTL', 'OTP_TTL');
        defined('OTP_VERIFIED_TTL') or define('OTP_VERIFIED_TTL', 'OTP_VERIFIED_TTL');
        defined('OTP_MAX_REQUESTS') or define('OTP_MAX_REQUESTS', 'OTP_MAX_REQUESTS');
        defined('OTP_REQUEST_WINDOW') or define('OTP_REQUEST_WINDOW', 'OTP_REQUEST_WINDOW');
        defined('OTP_MAX_ATTEMPTS') or define('OTP_MAX_ATTEMPTS', 'OTP_MAX_ATTEMPTS');

        // Tiered Assessment settings
        defined('ASSESSMENT_MAX_QUESTIONS') or define('ASSESSMENT_MAX_QUESTIONS', 'ASSESSMENT_MAX_QUESTIONS');
        defined('ASSESSMENT_PASSING_SCORE') or define('ASSESSMENT_PASSING_SCORE', 'ASSESSMENT_PASSING_SCORE');
        defined('ASSESSMENT_LEVEL_TIMEOUT_SECONDS') or define('ASSESSMENT_LEVEL_TIMEOUT_SECONDS', 'ASSESSMENT_LEVEL_TIMEOUT_SECONDS');

        // Ghana Card Verification settings
        defined('GHANA_CARD_MAX_ATTEMPTS') or define('GHANA_CARD_MAX_ATTEMPTS', 'GHANA_CARD_MAX_ATTEMPTS');
        defined('VERIFICATION_PROFILE_IMAGE_UPLOAD_URL') or define('VERIFICATION_PROFILE_IMAGE_UPLOAD_URL', 'VERIFICATION_PROFILE_IMAGE_UPLOAD_URL');
        defined('VERIFICATION_PROFILE_IMAGE_STORAGE_DISK') or define('VERIFICATION_PROFILE_IMAGE_STORAGE_DISK', 'VERIFICATION_PROFILE_IMAGE_STORAGE_DISK');
        defined('VERIFICATION_PROFILE_IMAGE_STORAGE_DIR') or define('VERIFICATION_PROFILE_IMAGE_STORAGE_DIR', 'VERIFICATION_PROFILE_IMAGE_STORAGE_DIR');
        defined('VERIFICATION_PROFILE_IMAGE_TIMEOUT_SECONDS') or define('VERIFICATION_PROFILE_IMAGE_TIMEOUT_SECONDS', 'VERIFICATION_PROFILE_IMAGE_TIMEOUT_SECONDS');
        defined('VERIFICATION_PROFILE_IMAGE_ENABLED') or define('VERIFICATION_PROFILE_IMAGE_ENABLED', 'VERIFICATION_PROFILE_IMAGE_ENABLED');

        // Booking System parameters
        defined('SHORT_SLOTS_PERCENTAGE') or define('SHORT_SLOTS_PERCENTAGE', 'SHORT_SLOTS_PERCENTAGE');
        defined('LONG_SLOTS_PERCENTAGE') or define('LONG_SLOTS_PERCENTAGE', 'LONG_SLOTS_PERCENTAGE');
        defined('DEFAULT_PROTOCOL_RESERVED_LONG_SLOTS') or define('DEFAULT_PROTOCOL_RESERVED_LONG_SLOTS', 'DEFAULT_PROTOCOL_RESERVED_LONG_SLOTS');
        defined('DEFAULT_PROTOCOL_RESERVED_SHORT_SLOTS') or define('DEFAULT_PROTOCOL_RESERVED_SHORT_SLOTS', 'DEFAULT_PROTOCOL_RESERVED_SHORT_SLOTS');
        defined('WAITLIST_NOTIFY_LIMIT') or define('WAITLIST_NOTIFY_LIMIT', 'WAITLIST_NOTIFY_LIMIT');
        defined('AVAILABILITY_CACHE_TTL') or define('AVAILABILITY_CACHE_TTL', 'AVAILABILITY_CACHE_TTL');

        defined('APPLICATION_REVIEW_IFRAME_URL') or define('APPLICATION_REVIEW_IFRAME_URL', 'APPLICATION_REVIEW_IFRAME_URL');

        // Session Reminder settings
        defined('ONE_WEEK_REMINDER') or define('ONE_WEEK_REMINDER', 'ONE_WEEK_REMINDER');
        defined('THREE_DAYS_REMINDER') or define('THREE_DAYS_REMINDER', 'THREE_DAYS_REMINDER');
        defined('ONE_DAY_REMINDER') or define('ONE_DAY_REMINDER', 'ONE_DAY_REMINDER');

        // Admission Revocation Cooldown
        defined('ADMISSION_REVOCATION_COOLDOWN_HOURS') or define('ADMISSION_REVOCATION_COOLDOWN_HOURS', 'ADMISSION_REVOCATION_COOLDOWN_HOURS');

        // Protocal Booking settings
        defined('DEFAULT_PROTOCOL_RESERVED_LONG_SLOTS') or define('DEFAULT_PROTOCOL_RESERVED_LONG_SLOTS', 'DEFAULT_PROTOCOL_RESERVED_LONG_SLOTS');
        defined('DEFAULT_PROTOCOL_RESERVED_SHORT_SLOTS') or define('DEFAULT_PROTOCOL_RESERVED_SHORT_SLOTS', 'DEFAULT_PROTOCOL_RESERVED_SHORT_SLOTS');

        defined('PROTOCOL_BOOKING_CUTOFF_HOURS_BEFORE_SESSION') or define('PROTOCOL_BOOKING_CUTOFF_HOURS_BEFORE_SESSION', 'PROTOCOL_BOOKING_CUTOFF_HOURS_BEFORE_SESSION');
        defined('PROTOCOL_BOOKING_CUTOFF_HOURS_BEFORE_SESSION_FOR_SHORT_SLOTS') or define('PROTOCOL_BOOKING_CUTOFF_HOURS_BEFORE_SESSION_FOR_SHORT_SLOTS', 'PROTOCOL_BOOKING_CUTOFF_HOURS_BEFORE_SESSION_FOR_SHORT_SLOTS');

        defined('PROTOCOL_BOOKING_CUTOFF_HOURS_BEFORE_SESSION_FOR_LONG_SLOTS') or define('PROTOCOL_BOOKING_CUTOFF_HOURS_BEFORE_SESSION_FOR_LONG_SLOTS', 'PROTOCOL_BOOKING_CUTOFF_HOURS_BEFORE_SESSION_FOR_LONG_SLOTS');
        defined('WAITLIST_BOOKING_CUTOFF_HOURS_BEFORE_SESSION') or define('WAITLIST_BOOKING_CUTOFF_HOURS_BEFORE_SESSION', 'WAITLIST_BOOKING_CUTOFF_HOURS_BEFORE_SESSION');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
        $this->loadDatabaseConfig();
    }

    private function loadDatabaseConfig()
    {
        try {
            // code...
            $configs = AppConfig::all(); // Fetch all configurations
            foreach ($configs as $config) {
                $value = $config->value;

                // Apply caching logic.
                if ($config->is_cached) {
                    $value = Cache::rememberForever($config->key, function () use ($config) {
                        return AppConfig::castValue($config);
                    });
                } else {
                    $value = AppConfig::castValue($config);
                }
                Config::set($config->key, $value);
                // dump($config->key, $value);
            }
        } catch (\Throwable $th) {
            // throw $th;
        }

        // dd(Config::all());
    }
}
