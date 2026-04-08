<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('centres') || Schema::hasColumn('centres', 'constituency_id')) {
            return;
        }

        Schema::table('centres', function (Blueprint $table) {
            $table->foreignId('constituency_id')
                ->nullable()
                ->after('branch_id')
                ->constrained('constituencies')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('centres') || !Schema::hasColumn('centres', 'constituency_id')) {
            return;
        }

        Schema::table('centres', function (Blueprint $table) {
            $table->dropForeign(['constituency_id']);
            $table->dropColumn('constituency_id');
        });
    }
};
