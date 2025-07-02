<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_name');
            $table->string('course')->nullable();
            $table->string('location')->nullable();
            $table->timestamps();
        });

        Schema::create('course_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('course_id')->nullable();
            $table->integer('limit')->default(100);
            $table->string('course_time');
            $table->string('session');
            $table->timestamps();
        });

        Schema::create('user_admission', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('course_id')->nullable();
            $table->dateTime('confirmed')->nullable();
            $table->dateTime('submitted')->nullable();
            $table->dateTime('email_sent')->nullable();
            $table->string('location')->nullable();
            $table->string('session')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_admission');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('course_sessions');
    }
};
