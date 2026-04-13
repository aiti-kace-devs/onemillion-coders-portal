<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programmes', function (Blueprint $table) {
            $table->unsignedTinyInteger('duration_in_days')->nullable()->after('duration');
            $table->unsignedTinyInteger('time_allocation')->nullable()->after('duration_in_days');
        });

        // Backfill existing programmes
        DB::table('programmes')->chunkById(100, function ($programmes) {
            foreach ($programmes as $p) {
                $hours = (int) filter_var($p->duration, FILTER_SANITIZE_NUMBER_INT);
                if ($hours <= 0) {
                    continue;
                }

                if ($hours < 40) {
                    $alloc = 2;
                    $days  = (int) ceil($hours / 2);
                } elseif ($hours <= 80) {
                    $alloc = 4;
                    $days  = 20;
                } elseif ($hours <= 120) {
                    $alloc = 4;
                    $days  = 30;
                } elseif ($hours <= 160) {
                    $alloc = 4;
                    $days  = 40;
                } elseif ($hours <= 200) {
                    $alloc = 4;
                    $days  = 50;
                } else {
                    $alloc = 4;
                    $days  = 60;
                }

                DB::table('programmes')
                    ->where('id', $p->id)
                    ->update(['duration_in_days' => $days, 'time_allocation' => $alloc]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('programmes', function (Blueprint $table) {
            $table->dropColumn(['duration_in_days', 'time_allocation']);
        });
    }
};
