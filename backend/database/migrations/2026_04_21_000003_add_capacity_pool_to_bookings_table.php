<?php

use App\Models\Booking;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'capacity_pool')) {
                $table->string('capacity_pool', 20)->nullable()->after('is_protocol');
                $table->index(['capacity_pool', 'status'], 'bookings_capacity_pool_status_index');
            }
        });

        if (Schema::hasColumn('bookings', 'capacity_pool')) {
            DB::table('bookings')
                ->whereNull('capacity_pool')
                ->where('is_protocol', true)
                ->update(['capacity_pool' => Booking::CAPACITY_POOL_RESERVED]);

            DB::table('bookings')
                ->whereNull('capacity_pool')
                ->update(['capacity_pool' => Booking::CAPACITY_POOL_STANDARD]);
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'capacity_pool')) {
                $table->dropIndex('bookings_capacity_pool_status_index');
                $table->dropColumn('capacity_pool');
            }
        });
    }
};
