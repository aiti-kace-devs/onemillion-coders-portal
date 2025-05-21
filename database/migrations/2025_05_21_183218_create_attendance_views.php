<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement(
            "CREATE OR REPLACE VIEW " . COURSE_SESSION_ATTENDANCE_VIEW . " AS
            SELECT
            s.course_name,
            at.attendance_date,
            at.total AS total,
            s.id AS course_id,
            cs.name AS session_name,
            b.title AS branch_name,
            p.title AS programme_name,
            c.title AS centre_name,
            cs.id AS session_id,
            b.id AS branch_id,
            p.id AS programme_id,
            c.id AS centre_id
        FROM
            course_sessions cs
        LEFT JOIN
            courses s ON s.id = cs.course_id
        LEFT JOIN
            programmes p ON p.id = s.programme_id
        LEFT JOIN
            centres c ON c.id = s.centre_id
        LEFT JOIN
            branches b ON b.id = c.branch_id
        LEFT JOIN (
            SELECT
                DATE_FORMAT(a.date, '%Y-%m-%d') AS attendance_date,
                COUNT(DISTINCT a.user_id) AS total,
                ua.session AS session_id,
                ua.course_id
            FROM
                attendances a
            LEFT JOIN
                user_admission ua ON ua.user_id = a.user_id AND ua.course_id = a.course_id
            GROUP BY
                ua.session,
                DATE_FORMAT(a.date, '%Y-%m-%d'),
                ua.course_id
        ) AS at ON at.session_id = cs.id AND at.course_id = s.id
        ORDER BY
    s.course_name, cs.name, at.attendance_date"
        );

        DB::statement(
            "CREATE OR REPLACE VIEW " . COURSE_ATTENDANCE_VIEW . " AS
        SELECT
            s.course_name,
            at.attendance_date,
            at.total AS total,
            s.id AS course_id,
            cs.name AS session_name,
            b.title AS branch_name,
            p.title AS programme_name,
            c.title AS centre_name,
            cs.id AS session_id,
            b.id AS branch_id,
            p.id AS programme_id,
            c.id AS centre_id
        FROM
            courses s
        LEFT JOIN
            course_sessions cs ON cs.course_id = s.id
        LEFT JOIN
            programmes p ON p.id = s.programme_id
        LEFT JOIN
            centres c ON c.id = s.centre_id
        LEFT JOIN
            branches b ON b.id = c.branch_id
        LEFT JOIN (
            SELECT
                DATE_FORMAT(a.date, '%Y-%m-%d') AS attendance_date,
                COUNT(DISTINCT a.user_id) AS total,
                ua.course_id
            FROM
                attendances a
            LEFT JOIN
                user_admission ua ON ua.user_id = a.user_id AND ua.course_id = a.course_id
            GROUP BY
                ua.course_id,
                DATE_FORMAT(a.date, '%Y-%m-%d')
        ) AS at ON at.course_id = s.id
        ORDER BY
            s.course_name, cs.name, at.attendance_date"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
