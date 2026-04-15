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
        Schema::table('programme_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('programme_batches', 'centre_id')) {
                $table->foreignId('centre_id')
                    ->after('programme_id')
                    ->constrained('centres')
                    ->cascadeOnDelete();
                
                // Update unique constraint to include centre_id
                $table->dropUnique('pb_batch_programme_start_unique');
                $table->unique(
                    ['admission_batch_id', 'programme_id', 'centre_id', 'start_date'],
                    'pb_batch_programme_centre_start_unique'
                );
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programme_batches', function (Blueprint $table) {
            if (Schema::hasColumn('programme_batches', 'centre_id')) {
                $table->dropForeign(['centre_id']);
                $table->dropColumn('centre_id');
                
                // Restore original unique constraint
                $table->dropUnique('pb_batch_programme_centre_start_unique');
                $table->unique(
                    ['admission_batch_id', 'programme_id', 'start_date'],
                    'pb_batch_programme_start_unique'
                );
            }
        });
    }
};
