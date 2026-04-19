<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('old_admissions', function (Blueprint $table) {
            // Drop the foreign key
            $table->dropForeign('old_admissions_session_foreign');

            // Make session column nullable
            $table->unsignedBigInteger('session')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('old_admissions', function (Blueprint $table) {
            // Revert column to NOT NULL
            $table->unsignedBigInteger('session')->nullable(false)->change();

            // Re-add the foreign key
            $table->foreign('session')
                  ->references('id')
                  ->on('sessions')
                  ->cascadeOnDelete();
        });
    }
};