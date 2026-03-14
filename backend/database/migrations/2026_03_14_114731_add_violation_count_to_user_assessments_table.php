<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_assessments', function (Blueprint $table) {
            $table->unsignedTinyInteger('violation_count')->default(0)->after('answered_question_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_assessments', function (Blueprint $table) {
            $table->dropColumn('violation_count');
        });
    }
};
