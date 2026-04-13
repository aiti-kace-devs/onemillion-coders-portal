<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('centres', function (Blueprint $table) {
            $table->unsignedTinyInteger('seat_count')->nullable()->after('is_ready');
            $table->unsignedTinyInteger('short_slots_per_day')->nullable()->after('seat_count');
            $table->unsignedTinyInteger('long_slots_per_day')->nullable()->after('short_slots_per_day');
        });
    }

    public function down(): void
    {
        Schema::table('centres', function (Blueprint $table) {
            $table->dropColumn(['seat_count', 'short_slots_per_day', 'long_slots_per_day']);
        });
    }
};
