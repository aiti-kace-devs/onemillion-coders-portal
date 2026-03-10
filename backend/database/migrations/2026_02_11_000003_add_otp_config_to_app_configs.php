<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds OTP config keys to app_configs for admin dashboard editing.
     * Uses insertOrIgnore so existing installs get the new keys.
     */
    public function up(): void
    {
        DB::table('app_configs')->insertOrIgnore([
            ['key' => 'OTP_TTL', 'value' => 600, 'type' => 'integer', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'OTP_VERIFIED_TTL', 'value' => 1800, 'type' => 'integer', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'OTP_MAX_REQUESTS', 'value' => 3, 'type' => 'integer', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'OTP_REQUEST_WINDOW', 'value' => 600, 'type' => 'integer', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'OTP_MAX_ATTEMPTS', 'value' => 5, 'type' => 'integer', 'is_cached' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('app_configs')->whereIn('key', [
            'OTP_TTL',
            'OTP_VERIFIED_TTL',
            'OTP_MAX_REQUESTS',
            'OTP_REQUEST_WINDOW',
            'OTP_MAX_ATTEMPTS',
        ])->delete();
    }
};
