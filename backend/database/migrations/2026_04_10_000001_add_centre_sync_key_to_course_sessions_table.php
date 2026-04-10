<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('course_sessions', 'centre_sync_key')) {
            return;
        }

        Schema::table('course_sessions', function (Blueprint $table) {
            $table->uuid('centre_sync_key')->nullable()->after('session_type');
            $table->index(['session_type', 'centre_sync_key'], 'course_sessions_session_type_centre_sync_key_index');
            $table->unique(['session_type', 'centre_id', 'centre_sync_key'], 'course_sessions_centre_sync_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('course_sessions', 'centre_sync_key')) {
            return;
        }

        Schema::table('course_sessions', function (Blueprint $table) {
            $table->dropUnique('course_sessions_centre_sync_unique');
            $table->dropIndex('course_sessions_session_type_centre_sync_key_index');
            $table->dropColumn('centre_sync_key');
        });
    }
};
