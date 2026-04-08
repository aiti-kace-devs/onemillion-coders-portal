<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_partner_progress_history_rollups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_partner_progress_id');
            $table->unsignedBigInteger('user_id');
            $table->string('partner_code', 64)->index();
            $table->unsignedBigInteger('course_id')->nullable();
            $table->date('period_date')->index();
            $table->string('granularity', 16)->default('daily');
            $table->timestamp('last_captured_at')->index();
            $table->decimal('overall_progress_percent', 5, 2)->nullable();
            $table->json('metrics_json')->nullable();
            $table->timestamps();

            $table->unique(
                ['student_partner_progress_id', 'period_date', 'granularity'],
                'spph_rollups_snapshot_period_granularity_unique'
            );
            $table->foreign('student_partner_progress_id', 'spph_rollups_snapshot_fk')
                ->references('id')
                ->on('student_partner_progress')
                ->cascadeOnDelete();
            $table->foreign('user_id', 'spph_rollups_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('course_id', 'spph_rollups_course_fk')
                ->references('id')
                ->on('courses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_partner_progress_history_rollups');
    }
};
