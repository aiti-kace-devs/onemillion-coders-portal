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
        Schema::table('admission_rejections', function (Blueprint $table) {
            $table->enum('rejection_source', ['self', 'admin', 'system'])->default('self');

            $table->index('rejection_source', 'rejection_source_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admission_rejections', function (Blueprint $table) {
            $table->dropIndex('rejection_source_index');
            $table->dropColumn('rejection_source');
        });
    }
};
