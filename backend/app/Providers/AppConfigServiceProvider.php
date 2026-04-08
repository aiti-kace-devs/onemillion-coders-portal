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

        defined('PARTNER_PROGRESS_STALE_AFTER_DAYS') or define('PARTNER_PROGRESS_STALE_AFTER_DAYS', 'PARTNER_PROGRESS_STALE_AFTER_DAYS');
        defined('PARTNER_PROGRESS_REMINDER_COOLDOWN_HOURS') or define('PARTNER_PROGRESS_REMINDER_COOLDOWN_HOURS', 'PARTNER_PROGRESS_REMINDER_COOLDOWN_HOURS');
        defined('PARTNER_PROGRESS_STARTOCODE_PARTNER_CODE') or define('PARTNER_PROGRESS_STARTOCODE_PARTNER_CODE', 'PARTNER_PROGRESS_STARTOCODE_PARTNER_CODE');
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
            //code...
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
            //throw $th;
        }


        // dd(Config::all());
    }
}
