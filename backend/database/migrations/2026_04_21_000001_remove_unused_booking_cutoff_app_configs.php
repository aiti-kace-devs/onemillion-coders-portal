<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const KEYS = [
        'PROTOCOL_BOOKING_CUTOFF_HOURS_BEFORE_SESSION',
        'PROTOCOL_BOOKING_CUTOFF_HOURS_BEFORE_SESSION_FOR_SHORT_SLOTS',
        'PROTOCOL_BOOKING_CUTOFF_HOURS_BEFORE_SESSION_FOR_LONG_SLOTS',
        'WAITLIST_BOOKING_CUTOFF_HOURS_BEFORE_SESSION',
    ];

    public function up(): void
    {
        DB::table('app_configs')->whereIn('key', self::KEYS)->delete();
    }

    public function down(): void
    {
        DB::table('app_configs')->insertOrIgnore([
            [
                'key' => 'PROTOCOL_BOOKING_CUTOFF_HOURS_BEFORE_SESSION',
                'value' => 2,
                'type' => 'integer',
                'is_cached' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'PROTOCOL_BOOKING_CUTOFF_HOURS_BEFORE_SESSION_FOR_SHORT_SLOTS',
                'value' => 1,
                'type' => 'integer',
                'is_cached' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'PROTOCOL_BOOKING_CUTOFF_HOURS_BEFORE_SESSION_FOR_LONG_SLOTS',
                'value' => 3,
                'type' => 'integer',
                'is_cached' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'WAITLIST_BOOKING_CUTOFF_HOURS_BEFORE_SESSION',
                'value' => 1,
                'type' => 'integer',
                'is_cached' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
};
