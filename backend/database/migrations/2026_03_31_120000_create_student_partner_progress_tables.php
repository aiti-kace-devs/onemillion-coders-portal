<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_course_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('partner_code', 64)->index();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->string('course_name_pattern')->nullable();
            $table->unsignedBigInteger('learning_path_id')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('meta_json')->nullable();
            $table->timestamps();
        });

        Schema::create('student_partner_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('partner_code', 64)->index();
            $table->string('omcp_id')->index();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->unsignedBigInteger('learning_path_id')->nullable();
            $table->string('partner_student_ref')->nullable();
            $table->json('progress_summary_json')->nullable();
            $table->json('progress_raw_json')->nullable();
            $table->decimal('overall_progress_percent', 5, 2)->nullable();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamp('last_synced_at')->nullable()->index();
            $table->timestamp('last_sync_attempt_at')->nullable();
            $table->timestamp('stale_after_at')->nullable()->index();
            $table->timestamp('last_reminder_sent_at')->nullable()->index();
            $table->unsignedInteger('reminder_count')->default(0);
            $table->string('last_sync_error')->nullable();
            $table->timestamps();

            $table->unique(['partner_code', 'omcp_id', 'course_id'], 'partner_omcp_course_unique');
            $table->index(['user_id', 'partner_code'], 'partner_progress_user_partner_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_partner_progress');
        Schema::dropIfExists('partner_course_mappings');
    }
};
