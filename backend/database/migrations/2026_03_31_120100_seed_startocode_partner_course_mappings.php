<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('partner_course_mappings')) {
            return;
        }

        $now = now();
        // Must match App Config PARTNER_PROGRESS_STARTOCODE_PARTNER_CODE / StartocodePartnerCode::current().
        $partnerCode = 'startocode';
        $rows = [
            ['course_name_pattern' => 'Introduction to Programming'],
            ['course_name_pattern' => 'AWS Cloud Practitioner'],
            ['course_name_pattern' => 'Frontend development'],
            ['course_name_pattern' => 'Backend development'],
            ['course_name_pattern' => 'Fullstack development'],
        ];

        foreach ($rows as $row) {
            $exists = DB::table('partner_course_mappings')
                ->where('partner_code', $partnerCode)
                ->where('course_name_pattern', $row['course_name_pattern'])
                ->exists();

            if (!$exists) {
                DB::table('partner_course_mappings')->insert([
                    'partner_code' => $partnerCode,
                    'course_name_pattern' => $row['course_name_pattern'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('partner_course_mappings')) {
            return;
        }

        $partnerCode = 'startocode';

        DB::table('partner_course_mappings')
            ->where('partner_code', $partnerCode)
            ->whereIn('course_name_pattern', [
                'Introduction to Programming',
                'AWS Cloud Practitioner',
                'Frontend development',
                'Backend development',
                'Fullstack development',
            ])
            ->delete();
    }
};
