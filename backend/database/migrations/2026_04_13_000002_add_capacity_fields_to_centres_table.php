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
        Schema::table('centres', function (Blueprint $table) {
            $table->tinyInteger('seat_count')->nullable()->after('branch_id');
            $table->tinyInteger('short_slots_per_day')->nullable()->after('seat_count');
            $table->tinyInteger('long_slots_per_day')->nullable()->after('short_slots_per_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('centres', function (Blueprint $table) {
            $table->dropColumn(['seat_count', 'short_slots_per_day', 'long_slots_per_day']);
        });
    }
};
