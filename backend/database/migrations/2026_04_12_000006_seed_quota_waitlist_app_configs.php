<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('app_configs')->insertOrIgnore([
            [
                'key'       => 'SHORT_SLOTS_PERCENTAGE',
                'value'     => '40',
                'type'      => 'integer',
                'is_cached' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key'       => 'LONG_SLOTS_PERCENTAGE',
                'value'     => '60',
                'type'      => 'integer',
                'is_cached' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key'       => 'WAITLIST_NOTIFICATION_COUNT',
                'value'     => '5',
                'type'      => 'integer',
                'is_cached' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('app_configs')->whereIn('key', [
            'SHORT_SLOTS_PERCENTAGE',
            'LONG_SLOTS_PERCENTAGE',
            'WAITLIST_NOTIFICATION_COUNT',
        ])->delete();
    }
};
