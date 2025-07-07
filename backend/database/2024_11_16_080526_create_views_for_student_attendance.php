<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("CREATE VIEW vUserCourseAttendance AS
        SELECT ua.user_id AS user_id, u.name AS user_name, u.gender AS user_gender, u.network_type AS user_network_type, u.contact AS user_contact, s.id AS course_id, s.location AS course_location, s.course_name AS course_name, at.attendance_date, at.total
        FROM user_admission ua
        LEFT JOIN users u ON u.userId = ua.user_id
        LEFT JOIN courses s ON s.id = ua.course_id
        LEFT JOIN (
        SELECT DATE_FORMAT(a.date, '%Y-%m-%d') AS attendance_date,
               COUNT(*) AS total,
               MAX(a.user_id) AS user_id
        FROM attendances a
        GROUP BY a.user_id , attendance_date
        ) AS at ON at.user_id = ua.user_id");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('views_for_student_attendance');
    }
};
