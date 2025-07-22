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
        // Schema::table('course_categories', function (Blueprint $table) {
        //     $table->text('icon')->nullable()->after('description');
        // });

        // Schema::table('courses', function (Blueprint $table) {
        //     $table->text('no_of_days')->nullable()->after('duration');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('course_categories', function (Blueprint $table) {
        //     $table->dropColumn([
        //         'icon'
        //     ]);
        // });

        // Schema::table('courses', function (Blueprint $table) {
        //     $table->dropColumn([
        //         'no_of_days'
        //     ]);
        // });
    }
};
