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
        Schema::table('users', function (Blueprint $table) {
            $table->string('verification_block_reason')->nullable()->after('is_verification_blocked');
            $table->text('verification_block_message')->nullable()->after('verification_block_reason');
            $table->timestamp('verification_attempts_reset_at')->nullable()->after('verification_block_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'verification_block_reason',
                'verification_block_message',
                'verification_attempts_reset_at',
            ]);
        });
    }
};
