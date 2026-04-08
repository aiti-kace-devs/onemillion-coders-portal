<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_configs')) {
            return;
        }

        DB::table('app_configs')->insertOrIgnore([
            'key' => 'PARTNER_PROGRESS_REMINDER_COOLDOWN_HOURS',
            'value' => '24',
            'type' => 'integer',
            'is_cached' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('app_configs')) {
            return;
        }

        DB::table('app_configs')->where('key', 'PARTNER_PROGRESS_REMINDER_COOLDOWN_HOURS')->delete();
    }
};
