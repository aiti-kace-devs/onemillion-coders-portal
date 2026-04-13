<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programme_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('admission_batches')->cascadeOnDelete();
            $table->unsignedSmallInteger('duration')->nullable(); // duration_in_days copy
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('available_slots')->default(0);
            $table->timestamps();
            $table->index(['course_id', 'start_date']);
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programme_batches');
    }
};
