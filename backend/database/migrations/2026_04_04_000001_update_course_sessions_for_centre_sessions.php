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
            $table->unsignedBigInteger('course_id')->nullable()->change();
        });

        Schema::table('course_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('course_sessions', 'centre_id')) {
                $table->foreignId('centre_id')->nullable()->after('course_id')->constrained('centres');
            }

            if (!Schema::hasColumn('course_sessions', 'session_type')) {
                $table->string('session_type')->default('course')->after('centre_id');
            }

            $table->index(['session_type', 'centre_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_sessions', function (Blueprint $table) {
            $table->dropIndex('course_sessions_session_type_centre_id_index');

            if (Schema::hasColumn('course_sessions', 'centre_id')) {
                $table->dropForeign(['centre_id']);
                $table->dropColumn('centre_id');
            }

            if (Schema::hasColumn('course_sessions', 'session_type')) {
                $table->dropColumn('session_type');
            }
        });

        Schema::table('course_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->nullable(false)->change();
        });
    }
};
