<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_course_histories', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('centre_id')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->string('status', 20)->default('admitted');
            $table->boolean('support_status')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('course_id');

            $table->foreign('user_id')->references('userId')->on('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('course_id')->references('id')->on('courses')->nullOnDelete();
            $table->foreign('centre_id')->references('id')->on('centres')->nullOnDelete();
            $table->foreign('session_id')->references('id')->on('course_sessions')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('admission_batches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_course_histories');
    }
};
