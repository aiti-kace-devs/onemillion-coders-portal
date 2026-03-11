<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['course_id', 'date'], 'attendances_course_id_date_index');
        });

        Schema::table('user_admission', function (Blueprint $table) {
            $table->index(['course_id', 'confirmed'], 'user_admission_course_id_confirmed_index');
            $table->index(['course_id', 'session'], 'user_admission_course_id_session_index');
        });

        Schema::table('oex_results', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'oex_results_user_id_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_course_id_date_index');
        });

        Schema::table('user_admission', function (Blueprint $table) {
            $table->dropIndex('user_admission_course_id_confirmed_index');
            $table->dropIndex('user_admission_course_id_session_index');
        });

        Schema::table('oex_results', function (Blueprint $table) {
            $table->dropIndex('oex_results_user_id_created_at_index');
        });
    }
};

