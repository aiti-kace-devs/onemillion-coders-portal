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
        Schema::table('course_match', function (Blueprint $table) {
            if (!Schema::hasColumn('course_match', 'reference_source')) {
                $table->string('reference_source')->nullable()->after('type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_match', function (Blueprint $table) {
            if (Schema::hasColumn('course_match', 'reference_source')) {
                $table->dropColumn('reference_source');
            }
        });
    }
};
