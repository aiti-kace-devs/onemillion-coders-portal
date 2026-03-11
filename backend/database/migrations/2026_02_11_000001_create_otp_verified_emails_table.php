<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Persistent storage for verified emails. Used to:
     * - Track OTP verification across servers (cache-agnostic)
     * - Prevent reuse: one verification = one registration (used_at consumed)
     * - Reject "email already registered" early
     */
    public function up(): void
    {
        Schema::create('otp_verified_emails', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255)->unique();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['email', 'verified_at']);
            $table->index('used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_verified_emails');
    }
};
