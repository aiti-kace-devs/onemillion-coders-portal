<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programme_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_id')->constrained('programmes')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('admission_batches')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('centre_id')->nullable()->constrained('centres')->cascadeOnUpdate()->nullOnDelete();
            $table->string('scope', 32);
            $table->string('quota_key')->unique();
            $table->unsignedInteger('max_enrollments');
            $table->timestamps();

            $table->index(['programme_id', 'batch_id']);
        });

        Schema::create('centre_time_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centre_id')->constrained('centres')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('admission_batches')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('programme_id')->nullable()->constrained('programmes')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->cascadeOnUpdate()->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedInteger('capacity');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['centre_id', 'starts_at', 'ends_at']);
        });

        Schema::create('student_centre_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->foreignId('centre_time_block_id')->constrained('centre_time_blocks')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('user_admission_id')->nullable()->constrained('user_admission')->cascadeOnUpdate()->nullOnDelete();
            $table->string('status', 32);
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamps();

            $table->index(['user_id', 'centre_time_block_id']);
            $table->index(['centre_time_block_id', 'status']);
        });

        Schema::create('booking_waitlist', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->foreignId('centre_time_block_id')->constrained('centre_time_blocks')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('user_admission_id')->nullable()->constrained('user_admission')->cascadeOnUpdate()->nullOnDelete();
            $table->unsignedInteger('position');
            $table->string('status', 32);
            $table->timestamps();

            $table->index(['centre_time_block_id', 'status', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_waitlist');
        Schema::dropIfExists('student_centre_bookings');
        Schema::dropIfExists('centre_time_blocks');
        Schema::dropIfExists('programme_quotas');
    }
};
