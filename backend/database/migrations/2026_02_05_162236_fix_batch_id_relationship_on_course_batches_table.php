<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_batches', function (Blueprint $table) {
            // Drop wrong foreign key (branches)
            $table->dropForeign(['batch_id']);

            // Re-create correct foreign key (admission_batches)
            $table->foreign('batch_id')
                ->references('id')
                ->on('admission_batches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('course_batches', function (Blueprint $table) {
            // Rollback: drop admission_batches FK
            $table->dropForeign(['batch_id']);

            // Restore old (wrong) relationship if needed
            $table->foreign('batch_id')
                ->references('id')
                ->on('branches')
                ->nullOnDelete();
        });
    }
};
