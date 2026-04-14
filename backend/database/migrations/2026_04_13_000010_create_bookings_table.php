<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('user_id'); // mirrors users.userId
            $table->foreignId('programme_batch_id')->constrained('programme_batches')->cascadeOnDelete();
            $table->foreignId('course_session_id')->nullable()->constrained('course_sessions')->cascadeOnDelete();
            $table->foreignId('master_session_id')->constrained('master_sessions')->cascadeOnDelete();
            $table->foreignId('centre_id')->constrained('centres')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->enum('course_type', ['short', 'long']);
            $table->boolean('status')->default(true);
            $table->timestamp('booked_at')->useCurrent();
            $table->unsignedBigInteger('user_admission_id')->nullable();
            $table->timestamps();

            $table->foreign('user_admission_id')
                ->references('id')->on('user_admission')
                ->nullOnDelete();

            // userId foreign key to users table
            $table->foreign('user_id')
                ->references('userId')->on('users')
                ->cascadeOnDelete();

            $table->unique(['user_id', 'programme_batch_id']);
            $table->index(['course_type', 'status']);
            $table->index(['programme_batch_id', 'status']);
            $table->index(['course_session_id', 'status']);
            $table->index(['master_session_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
