<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_session_occupancy', function (Blueprint $table) {
            if (! Schema::hasColumn('daily_session_occupancy', 'protocol_occupied_count')) {
                $table->integer('protocol_occupied_count')->default(0)->after('occupied_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_session_occupancy', function (Blueprint $table) {
            if (Schema::hasColumn('daily_session_occupancy', 'protocol_occupied_count')) {
                $table->dropColumn('protocol_occupied_count');
            }
        });
    }
};
