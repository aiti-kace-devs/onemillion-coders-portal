<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {

            $table->index('centre_id', 'courses_centre_id_index');
            $table->index('programme_id', 'courses_programme_id_index');

            $table->dropUnique('courses_centre_id_programme_id_unique');

            $table->unique(
                ['centre_id', 'programme_id', 'batch_id'],
                'courses_centre_programme_batch_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {

            $table->dropUnique('courses_centre_programme_batch_unique');

            $table->dropIndex('courses_centre_id_index');
            $table->dropIndex('courses_programme_id_index');

            $table->unique(
                ['centre_id', 'programme_id'],
                'courses_centre_id_programme_id_unique'
            );
        });
    }
};
