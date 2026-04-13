<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_waitlist', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('userId')
                ->on('users')
                ->cascadeOnDelete();

            $table->unique(['user_id', 'course_id'], 'waitlist_user_course_unique');
            $table->index(['course_id', 'notified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_waitlist');
    }
};
