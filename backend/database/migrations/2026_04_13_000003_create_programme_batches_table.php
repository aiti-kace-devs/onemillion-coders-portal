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
        Schema::create('programme_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_batch_id')->constrained('admission_batches')->cascadeOnDelete();
            $table->foreignId('programme_id')->constrained('programmes')->cascadeOnDelete();
            $table->foreignId('centre_id')->constrained('centres')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->smallInteger('max_enrolments')->default(0);
            $table->smallInteger('available_slots')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['admission_batch_id', 'programme_id', 'centre_id', 'start_date']);

            // Composite index for availability queries
            $table->index(['admission_batch_id', 'programme_id', 'centre_id', 'status']);
            // Index for date range queries
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programme_batches');
    }
};
