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
        Schema::table('user_admission', function (Blueprint $table) {
            $table->foreignId('programme_batch_id')
                ->nullable()
                ->after('batch_id')
                ->constrained('programme_batches')
                ->nullOnDelete();

            // Make course_batch_id nullable explicitly for legacy records
            if (!Schema::hasColumn('user_admission', 'course_batch_id')) {
                $table->foreignId('course_batch_id')
                    ->nullable()
                    ->after('programme_batch_id')
                    ->constrained('programme_batches')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_admission', function (Blueprint $table) {
            $table->dropForeign(['programme_batch_id']);
            $table->dropColumn('programme_batch_id');

            if (Schema::hasColumn('user_admission', 'course_batch_id')) {
                $table->dropForeign(['course_batch_id']);
                $table->dropColumn('course_batch_id');
            }
        });
    }
};
