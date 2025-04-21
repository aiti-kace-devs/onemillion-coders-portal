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
        //
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
    }
}
