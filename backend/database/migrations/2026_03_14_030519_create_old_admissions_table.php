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
        Schema::create('old_admissions', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('course_id')->nullable();
            $table->dateTime('confirmed')->nullable();
            $table->dateTime('email_sent')->nullable();
            $table->string('session')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('old_admissions');
    }
};
