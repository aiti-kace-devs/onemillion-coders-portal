<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programme_batches', function (Blueprint $table) {
            if (! Schema::hasColumn('programme_batches', 'max_enrolments')) {
                $table->integer('max_enrolments')->nullable()->after('end_date');
            }
            if (! Schema::hasColumn('programme_batches', 'available_slots')) {
                $table->integer('available_slots')->nullable()->after('max_enrolments');
            }
        });
    }

    public function down(): void
    {
        Schema::table('programme_batches', function (Blueprint $table) {
            if (Schema::hasColumn('programme_batches', 'available_slots')) {
                $table->dropColumn('available_slots');
            }
            if (Schema::hasColumn('programme_batches', 'max_enrolments')) {
                $table->dropColumn('max_enrolments');
            }
        });
    }
};
