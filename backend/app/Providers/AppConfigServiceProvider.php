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
        defined('MINIMUM_EXAM_PASS_PERCENTAGE') or define('MINIMUM_EXAM_PASS_PERCENTAGE', 'MINIMUM_EXAM_PASS_PERCENTAGE');
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
