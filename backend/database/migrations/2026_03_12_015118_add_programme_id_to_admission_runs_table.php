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
        Schema::table('admission_runs', function (Blueprint $table) {
            $table->foreignId('programme_id')->nullable()->after('id')->constrained('programmes')->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->change();
            $table->index('programme_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admission_runs', function (Blueprint $table) {
            $table->dropForeign(['programme_id']);
            $table->dropIndex(['programme_id']);
            $table->dropColumn('programme_id');
            $table->foreignId('course_id')->nullable(false)->change();
        });
    }
};
