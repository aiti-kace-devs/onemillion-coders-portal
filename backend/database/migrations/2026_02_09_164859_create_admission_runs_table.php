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
        Schema::create('admission_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('batch_id')->constrained('admission_batches')->onDelete('cascade');
            $table->foreignId('run_by')->nullable()->constrained('admins')->onDelete('set null');
            $table->timestamp('run_at');
            $table->json('rules_applied')->nullable();
            $table->integer('selected_count')->default(0);
            $table->integer('admitted_count')->default(0);
            $table->integer('emailed_count')->default(0);
            $table->integer('accepted_count')->default(0);
            $table->integer('rejected_count')->default(0);
            $table->integer('manual_count')->default(0);
            $table->integer('automated_count')->default(0);
            $table->json('preview_data')->nullable();
            $table->enum('status', ['preview', 'completed', 'failed'])->default('completed');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('course_id');
            $table->index('batch_id');
            $table->index('run_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_runs');
    }
};
