<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('app_configs')) {
            return;
        }

        DB::table('app_configs')->insertOrIgnore([
            'key' => 'PARTNER_PROGRESS_STALE_AFTER_DAYS',
            'value' => '3',
            'type' => 'integer',
            'is_cached' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('app_configs')) {
            return;
        }

        DB::table('app_configs')->where('key', 'PARTNER_PROGRESS_STALE_AFTER_DAYS')->delete();
    }
};
