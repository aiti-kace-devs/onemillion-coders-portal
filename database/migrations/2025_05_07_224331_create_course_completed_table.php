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
        Schema::create('course_completed', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->unsignedBigInteger('course_id');
            $table->datetime('completed_at');

            $table->index('user_id');
            $table->index('course_id');
            $table->index('completed_at');

            // foreign keys
            $table->foreign('user_id')
                ->references('userId')
                ->on('users')->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('course_id')
                ->references('id')
                ->on('courses')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_completed');
    }
};
