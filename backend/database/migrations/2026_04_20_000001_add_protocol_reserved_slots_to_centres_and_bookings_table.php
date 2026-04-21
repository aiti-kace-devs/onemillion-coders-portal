<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('centres', function (Blueprint $table) {
            $table->tinyInteger('protocol_reserved_short_slots')->nullable()->after('long_slots_per_day');
            $table->tinyInteger('protocol_reserved_long_slots')->nullable()->after('protocol_reserved_short_slots');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('is_protocol')->default(false)->after('course_type');
            $table->index(['is_protocol', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['is_protocol', 'status']);
            $table->dropColumn('is_protocol');
        });

        Schema::table('centres', function (Blueprint $table) {
            $table->dropColumn(['protocol_reserved_short_slots', 'protocol_reserved_long_slots']);
        });
    }
};
