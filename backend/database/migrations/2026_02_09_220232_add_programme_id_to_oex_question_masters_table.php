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
        Schema::table('oex_question_masters', function (Blueprint $table) {
            $table->unsignedBigInteger('programme_id')->nullable()->after('exam_id');
            $table->index('programme_id');
        });
    }

    public function down(): void
    {
        Schema::table('oex_question_masters', function (Blueprint $table) {
            $table->dropIndex(['programme_id']);
            $table->dropColumn('programme_id');
        });
    }
};
