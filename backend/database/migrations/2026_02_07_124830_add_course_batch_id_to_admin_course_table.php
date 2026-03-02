<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('admin_course', function (Blueprint $table) {
            $table->foreignId('course_batch_id')
                ->nullable()
                ->after('course_id')
                ->constrained('course_batches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admin_course', function (Blueprint $table) {
            $table->dropForeign(['course_batch_id']);
            $table->dropColumn('course_batch_id');
        });
    }
};
