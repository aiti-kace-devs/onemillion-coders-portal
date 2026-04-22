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
        Schema::create('partner_student_admissions', function (Blueprint $table) {
            $table->id();
            $table->string('user_id'); // Match users.userId type
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->foreignId('programme_id')->constrained('programmes')->onDelete('cascade');
            $table->string('external_user_id')->nullable(); // ID on Coursera, Startocode, etc.
            $table->string('enrollment_status')->default('pending');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('userId')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'partner_id', 'programme_id'], 'unique_student_partner_programme');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_student_admissions');
    }
};
