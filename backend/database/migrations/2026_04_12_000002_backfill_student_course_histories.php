<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            INSERT INTO student_course_histories
                (user_id, course_id, centre_id, session_id, status, support_status, started_at, notes, created_at, updated_at)
            SELECT
                ua.user_id,
                ua.course_id,
                c.centre_id,
                ua.session,
                CASE WHEN ua.confirmed IS NOT NULL THEN 'confirmed' ELSE 'admitted' END,
                u.support,
                COALESCE(ua.confirmed, ua.created_at),
                'Backfilled from user_admission',
                NOW(),
                NOW()
            FROM user_admission ua
            LEFT JOIN courses c ON c.id = ua.course_id
            LEFT JOIN users u ON u.userId = ua.user_id
        ");
    }

    public function down(): void
    {
        DB::table('student_course_histories')
            ->where('notes', 'Backfilled from user_admission')
            ->delete();
    }
};
