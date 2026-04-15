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
            $table->smallInteger('seat_count')->nullable()->change();
            $table->smallInteger('short_slots_per_day')->nullable()->change();
            $table->smallInteger('long_slots_per_day')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('centres', function (Blueprint $table) {
            $table->tinyInteger('seat_count')->nullable()->change();
            $table->tinyInteger('short_slots_per_day')->nullable()->change();
            $table->tinyInteger('long_slots_per_day')->nullable()->change();
        });
    }
};
