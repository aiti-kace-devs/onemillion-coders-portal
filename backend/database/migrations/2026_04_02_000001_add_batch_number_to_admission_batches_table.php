<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_batches', function (Blueprint $table) {
            $table->unsignedInteger('batch_number')->nullable()->after('year');
        });
    }

    public function down(): void
    {
        Schema::table('admission_batches', function (Blueprint $table) {
            $table->dropColumn('batch_number');
        });
    }
};
