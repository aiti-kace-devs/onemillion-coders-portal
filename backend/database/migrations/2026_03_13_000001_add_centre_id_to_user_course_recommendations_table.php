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
        Schema::table('user_course_recommendations', function (Blueprint $table) {
            $table->foreignId('centre_id')
                ->nullable()
                ->after('course_id')
                ->constrained('centres')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_course_recommendations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('centre_id');
        });
    }
};
