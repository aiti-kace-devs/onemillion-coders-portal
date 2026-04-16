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
        Schema::create('admission_waitlist', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->foreignId('course_id');
            $table->foreignId('programme_batch_id')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('userId')->on('users')->onDelete('cascade');
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            $table->foreign('programme_batch_id')->references('id')->on('programme_batches')->onDelete('set null');

            $table->index(['user_id', 'course_id'], 'waitlist_user_course_index');
            $table->index(['course_id', 'status'], 'waitlist_course_status_index');
            $table->index(['programme_batch_id', 'status'], 'waitlist_batch_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_waitlist');
    }
};
