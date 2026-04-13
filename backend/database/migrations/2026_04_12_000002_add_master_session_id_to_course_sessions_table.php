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
        Schema::table('course_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('course_sessions', 'master_session_id')) {
                $table->unsignedBigInteger('master_session_id')->nullable()->after('centre_sync_key');
                $table->foreign('master_session_id')->references('id')->on('master_sessions')->nullOnDelete();
                $table->index('master_session_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('course_sessions', 'master_session_id')) {
                $table->dropForeign(['master_session_id']);
                $table->dropIndex(['master_session_id']);
                $table->dropColumn('master_session_id');
            }
        });
    }
};
