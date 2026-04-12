<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Centre session definitions live on `course_sessions` with session_type = 'centre'
 * (see CentreSession model). The legacy `centre_sessions` table is unused.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('centre_sessions');
    }

    public function down(): void
    {
        // Recreate minimal structure if rollback is required (no data restore).
        Schema::create('centre_sessions', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('centre_id')->constrained('centres');
            $table->integer('limit')->default(100);
            $table->string('course_time');
            $table->string('session');
            $table->string('link')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->index('centre_id');
            $table->index('session');
        });
    }
};
