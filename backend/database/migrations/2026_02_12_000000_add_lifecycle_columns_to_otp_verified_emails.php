<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds lifecycle tracking columns to otp_verified_emails.
     *
     * Before this migration the table only tracked post-verification state.
     * Now it tracks the FULL lifecycle:
     *
     *  1. OTP sent       → row created, verified_at = NULL
     *  2. OTP verified   → verified_at = timestamp
     *  3. Registered      → used_at    = timestamp
     *
     * New columns:
     *  - otp_code_hash : bcrypt hash of the OTP code (audit + legitimacy proof)
     *  - expires_at    : when the OTP expires (cache-independent TTL check)
     *
     * verified_at is made NULLABLE to represent the "OTP sent, not yet verified" state.
     */
    public function up(): void
    {
        Schema::table('otp_verified_emails', function (Blueprint $table) {
            // Make verified_at nullable (null = OTP sent but not yet verified)
            $table->timestamp('verified_at')->nullable()->change();

            // Hashed OTP code — proves the row was created by a legitimate OTP send,
            // not manually inserted. Useful for identifying external-tool abuse.
            $table->string('otp_code_hash', 255)->nullable()->after('email');

            // Independent expiry timestamp — allows TTL checks without relying on cache
            $table->timestamp('expires_at')->nullable()->after('otp_code_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otp_verified_emails', function (Blueprint $table) {
            $table->dropColumn(['otp_code_hash', 'expires_at']);

            // Revert verified_at back to non-nullable
            $table->timestamp('verified_at')->nullable(false)->change();
        });
    }
};
