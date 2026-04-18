<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('old_admissions', function (Blueprint $table) {
            $table->unsignedBigInteger('centre_id')->nullable()->after('course_id');
            $table->unsignedBigInteger('batch_id')->nullable()->after('centre_id');
            $table->string('status', 20)->default('admitted')->after('session');
            $table->boolean('support_status')->nullable()->after('status');
            $table->timestamp('started_at')->nullable()->after('support_status');
            $table->timestamp('ended_at')->nullable()->after('started_at');
            $table->text('notes')->nullable()->after('ended_at');

            $table->foreign('centre_id')->references('id')->on('centres')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('admission_batches')->nullOnDelete();
            $table->index(['user_id', 'status']);
        });

        // Backfill existing rows
        DB::statement("
            UPDATE old_admissions oa
            LEFT JOIN courses c ON c.id = oa.course_id
            LEFT JOIN users u ON u.userId = oa.user_id
            SET
                oa.centre_id = c.centre_id,
                oa.status = CASE WHEN oa.confirmed IS NOT NULL THEN 'confirmed' ELSE 'admitted' END,
                oa.started_at = COALESCE(oa.confirmed, oa.created_at),
                oa.support_status = u.support
        ");
    }

    public function down(): void
    {
        Schema::table('old_admissions', function (Blueprint $table) {
            $table->dropForeign(['centre_id']);
            $table->dropForeign(['batch_id']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropColumn(['centre_id', 'batch_id', 'status', 'support_status', 'started_at', 'ended_at', 'notes']);
        });
    }
};
