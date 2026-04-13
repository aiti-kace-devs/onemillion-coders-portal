<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revoked_admissions', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('programme_batch_id')->nullable();
            $table->unsignedBigInteger('session')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('originally_confirmed_at')->nullable();
            $table->timestamp('revoked_at');
            $table->timestamps();

            $table->index('user_id');
            $table->index('course_id');
            $table->index('revoked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revoked_admissions');
    }
};
