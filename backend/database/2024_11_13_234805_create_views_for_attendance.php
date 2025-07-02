<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("CREATE VIEW vDailyCourseAttendance AS
       SELECT CONCAT(s.location, ' - ', s.course_name) AS course_name, at. attendance_date, at.total, at.course_id
       FROM user_admission ua
       LEFT JOIN courses s ON s.id = ua.course_id
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
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW vDailyCourseAttendance");
    }
};
