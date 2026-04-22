<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('app_configs')->updateOrInsert(
            ['key' => 'AUTO_ADMIT'],
            [
                'value' => '0',
                'type' => 'boolean',
                'is_cached' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('app_configs')->where('key', 'AUTO_ADMIT')->delete();
    }
};
