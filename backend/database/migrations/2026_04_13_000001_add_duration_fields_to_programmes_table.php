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
        Schema::table('programmes', function (Blueprint $table) {
            // $table->smallInteger('duration_hours')->nullable()->after('duration');
            $table->tinyInteger('duration_in_days')->nullable()->after('duration');
            $table->tinyInteger('time_allocation')->nullable()->after('duration_in_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programmes', function (Blueprint $table) {
            $table->dropColumn(['duration_in_days', 'time_allocation']);
        });
    }
};
