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

        Schema::table('oex_question_masters', function (Blueprint $table) {
            $table->unsignedBigInteger('exam_set_id')->change();
        });

        Schema::table('oex_results', function (Blueprint $table) {
            $table->unsignedBigInteger('exam_set')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
