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
            $table->unsignedBigInteger('course_id');
            $table->dateTime('confirmed')->nullable();
            $table->dateTime('email_sent')->nullable();
            $table->unsignedBigInteger('session')->nullable();
            $table->timestamps();

            $table->foreign('session')->references('id')->on('course_sessions')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('course_id')->references('id')->on('courses')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('user_id')->references('userId')->on('users')
                ->onDelete('restrict')->onUpdate('cascade');
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
