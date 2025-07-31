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
        Schema::create('programme_course_match_options', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('programme_id')->constrained('programmes');
            $table->foreignId('course_match_option_id')->constrained('course_match_options');
            $table->index('programme_id');
            $table->index('course_match_option_id');
            $table->unique(['programme_id', 'course_match_option_id'], 'programme_course_match_option_unq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programme_course_match_options');
    }
};
