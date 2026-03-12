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
        Schema::create('user_course_recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->unsignedSmallInteger('rank')->nullable();
            $table->unsignedSmallInteger('match_percentage')->nullable();
            $table->unsignedSmallInteger('match_count')->nullable();
            $table->string('student_level')->nullable();
            $table->string('mode_of_delivery')->nullable();
            $table->string('provider')->nullable();
            $table->json('option_ids')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_course_recommendations');
    }
};
