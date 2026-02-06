<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('course_batches', function (Blueprint $table) {
            $table->string('duration')->nullable()->after('batch_id');
            // duration could be number of days / weeks / months (your choice)

            $table->date('start_date')->nullable()->after('duration');
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('course_batches', function (Blueprint $table) {
            $table->dropColumn([
                'duration',
                'start_date',
                'end_date',
            ]);
        });
    }
};
