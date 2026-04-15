<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_session_occupancy', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('centre_id')->constrained('centres')->cascadeOnDelete();
            $table->foreignId('master_session_id')->constrained('master_sessions')->cascadeOnDelete();
            $table->string('course_type'); // 'short' or 'long'
            $table->integer('occupied_count')->default(0);

            // Prevent duplicate rows for the same slot/day/centre
            $table->unique(
                ['date', 'centre_id', 'master_session_id'],
                'occupancy_unique_idx'
            );

            // Performance index for lookups by session + centre
            $table->index(
                ['master_session_id', 'centre_id'],
                'occupancy_session_centre_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_session_occupancy');
    }
};
