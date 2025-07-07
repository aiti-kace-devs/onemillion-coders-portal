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
        Schema::table('admins', function (Blueprint $table) {
            $table->string('status')->nullable()->after('remember_token');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->string('duration')->nullable()->after('location');
            $table->date('start_date')->nullable()->after('duration');
            $table->date('end_date')->nullable()->after('start_date');
            $table->string('status')->nullable()->after('end_date');
        });

        Schema::table('course_sessions', function (Blueprint $table) {
            $table->string('status')->nullable()->after('session');
        });

        Schema::table('user_admission', function (Blueprint $table) {
            $table->string('status')->nullable()->after('session');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->string('status')->nullable()->after('date');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->string('status')->nullable()->after('title');
        });


        Schema::table('programmes', function (Blueprint $table) {
            $table->string('duration')->nullable()->after('title');
            $table->date('start_date')->nullable()->after('duration');
            $table->date('end_date')->nullable()->after('start_date');
            $table->string('status')->nullable()->after('end_date');
        });


        Schema::table('centres', function (Blueprint $table) {
            $table->string('status')->nullable()->after('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('status');
        });


        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('duration');
            $table->dropColumn('start_date');
            $table->dropColumn('end_date');
            $table->dropColumn('status');
        });


        Schema::table('course_sessions', function (Blueprint $table) {
            $table->dropColumn('status');
        });



        Schema::table('user_admission', function (Blueprint $table) {
            $table->dropColumn('status');
        });


        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('status');
        });


        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('status');
        });


        Schema::table('programmes', function (Blueprint $table) {
            $table->dropColumn('duration');
            $table->dropColumn('start_date');
            $table->dropColumn('end_date');
            $table->dropColumn('status');
        });


        Schema::table('centres', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
