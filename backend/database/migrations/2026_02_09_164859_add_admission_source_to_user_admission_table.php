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
            $table->enum('admission_source', ['automated', 'manual'])->default('automated')->after('confirmed');
            $table->foreignId('admission_run_id')->nullable()->after('admission_source');
            $table->index('admission_source');
            $table->index('admission_run_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_admission', function (Blueprint $table) {
            $table->dropIndex(['admission_source', 'admission_run_id']);
            $table->dropColumn(['admission_source', 'admission_run_id']);
        });
    }
};
