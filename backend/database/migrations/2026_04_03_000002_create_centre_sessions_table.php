<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('centre_sessions', function (Blueprint $table) {
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('centre_sessions');
    }
};
