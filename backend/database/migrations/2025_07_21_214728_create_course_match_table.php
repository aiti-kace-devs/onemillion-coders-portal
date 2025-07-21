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
        Schema::create('course_match', function (Blueprint $table) {
            $table->id();
            $table->string('tag')->unique();
            $table->string('question');
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->integer('order')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_match');
    }
};
