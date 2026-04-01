<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_partner_progress_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_partner_progress_id');
            $table->unsignedBigInteger('user_id');
            $table->string('partner_code', 64)->index();
            $table->unsignedBigInteger('course_id')->nullable();
            $table->timestamp('captured_at')->index();
            $table->decimal('overall_progress_percent', 5, 2)->nullable();
            $table->decimal('video_percentage_complete', 5, 2)->nullable();
            $table->decimal('quiz_percentage_complete', 5, 2)->nullable();
            $table->decimal('project_percentage_complete', 5, 2)->nullable();
            $table->decimal('task_percentage_complete', 5, 2)->nullable();
            $table->json('payload_json')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'partner_code', 'captured_at'], 'partner_progress_hist_user_partner_time_idx');
            $table->foreign('student_partner_progress_id', 'spph_snapshot_fk')
                ->references('id')
                ->on('student_partner_progress')
                ->cascadeOnDelete();
            $table->foreign('user_id', 'spph_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('course_id', 'spph_course_fk')
                ->references('id')
                ->on('courses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_partner_progress_history');
    }
};
