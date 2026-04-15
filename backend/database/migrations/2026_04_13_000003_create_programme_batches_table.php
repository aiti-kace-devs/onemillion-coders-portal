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
        if (Schema::hasTable('programme_batches')) {
            return;
        }

        Schema::create('programme_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_batch_id')->constrained('admission_batches')->cascadeOnDelete();
            $table->foreignId('programme_id')->constrained('programmes')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['admission_batch_id', 'programme_id', 'start_date'],
                'pb_batch_programme_start_unique'
            );

            // Composite index for availability queries
            $table->index(
                ['admission_batch_id', 'programme_id', 'status'],
                'pb_batch_programme_status_idx'
            );
            // Index for date range queries
            $table->index(['start_date', 'end_date'], 'pb_start_end_date_idx');
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
