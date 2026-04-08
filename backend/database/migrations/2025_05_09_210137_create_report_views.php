<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS vDailyCourseAttendance');

        DB::statement("CREATE VIEW vDailyCourseAttendance AS
       SELECT s.course_name, at. attendance_date, at.total, at.course_id,
       cs.name AS session_name, b.title AS branch_name,
       p.title AS programme_name,
       c.title AS centre_name,
       cs.id AS session_id,
       b.id AS branch_id,
       p.id AS programme_id,
       c.id AS centre_id
       FROM user_admission ua
       LEFT JOIN courses s ON s.id = ua.course_id
       LEFT JOIN programmes p ON p.id = s.programme_id
       LEFT JOIN centres c ON c.id = s.centre_id
       LEFT JOIN branches b ON b.id = c.branch_id
       LEFT JOIN course_sessions cs ON cs.id = ua.session
       LEFT JOIN (
       SELECT DATE_FORMAT(a.date, '%Y-%m-%d') AS attendance_date,
               COUNT(*) AS total,
               MAX(a.course_id) AS course_id
       FROM attendances a
       GROUP BY a.course_id , attendance_date order by a.course_id, attendance_date asc
       ) AS at ON at.course_id = s.id");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS vDailyCourseAttendance');
    }
};
