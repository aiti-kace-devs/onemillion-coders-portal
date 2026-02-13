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
        Schema::create('oex_question_master_programme', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('oex_question_master_id');
            $table->unsignedBigInteger('programme_id');
            $table->timestamps();

            $table->foreign('oex_question_master_id')->references('id')->on('oex_question_masters')->onDelete('cascade');
            $table->foreign('programme_id')->references('id')->on('programmes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oex_question_master_programme');
    }
};
