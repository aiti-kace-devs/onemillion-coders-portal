<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_partner_progress')) {
            return;
        }

        if (!Schema::hasColumn('student_partner_progress', 'course_id_coalesced')) {
            Schema::table('student_partner_progress', function (Blueprint $table) {
                $table->unsignedBigInteger('course_id_coalesced')->storedAs('IFNULL(course_id,0)');
            });
        }

        // Drop nullable unique index that can allow duplicates when course_id is null.
        $existing = DB::select(
            "SELECT 1 AS found FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = 'student_partner_progress'
               AND index_name = 'partner_omcp_course_unique'
             LIMIT 1"
        );
        if (!empty($existing)) {
            Schema::table('student_partner_progress', function (Blueprint $table) {
                $table->dropUnique('partner_omcp_course_unique');
            });
        }

        $coalesced = DB::select(
            "SELECT 1 AS found FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = 'student_partner_progress'
               AND index_name = 'partner_omcp_course_coalesced_unique'
             LIMIT 1"
        );
        if (empty($coalesced)) {
            Schema::table('student_partner_progress', function (Blueprint $table) {
                $table->unique(
                    ['partner_code', 'omcp_id', 'course_id_coalesced'],
                    'partner_omcp_course_coalesced_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('student_partner_progress')) {
            return;
        }

        $coalesced = DB::select(
            "SELECT 1 AS found FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = 'student_partner_progress'
               AND index_name = 'partner_omcp_course_coalesced_unique'
             LIMIT 1"
        );
        if (!empty($coalesced)) {
            Schema::table('student_partner_progress', function (Blueprint $table) {
                $table->dropUnique('partner_omcp_course_coalesced_unique');
            });
        }

        $legacy = DB::select(
            "SELECT 1 AS found FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = 'student_partner_progress'
               AND index_name = 'partner_omcp_course_unique'
             LIMIT 1"
        );
        if (empty($legacy)) {
            Schema::table('student_partner_progress', function (Blueprint $table) {
                $table->unique(['partner_code', 'omcp_id', 'course_id'], 'partner_omcp_course_unique');
            });
        }

        if (Schema::hasColumn('student_partner_progress', 'course_id_coalesced')) {
            Schema::table('student_partner_progress', function (Blueprint $table) {
                $table->dropColumn('course_id_coalesced');
            });
        }
    }
};
