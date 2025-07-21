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
        Schema::create('course_match_options', function (Blueprint $table) {
            $table->id();
            $table->string('answer');
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->integer('order')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->foreignId('course_match_id')->constrained('course_match');
            $table->index('course_match_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_match_options');
    }
};
