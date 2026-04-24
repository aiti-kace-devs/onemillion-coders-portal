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
        Schema::table('protocol_lists', function (Blueprint $table) {
            $table->string('invitation_email_status', 32)
                ->default('pending')
                ->after('activation_email_sent_at');
            $table->timestamp('invitation_email_queued_at')
                ->nullable()
                ->after('invitation_email_status');
            $table->timestamp('invitation_email_last_attempt_at')
                ->nullable()
                ->after('invitation_email_queued_at');
            $table->timestamp('invitation_email_failed_at')
                ->nullable()
                ->after('invitation_email_last_attempt_at');
            $table->unsignedTinyInteger('invitation_email_attempts')
                ->default(0)
                ->after('invitation_email_failed_at');
            $table->text('invitation_email_failure_message')
                ->nullable()
                ->after('invitation_email_attempts');

            $table->index('invitation_email_status');
            $table->index('invitation_email_queued_at');
            $table->index('invitation_email_failed_at');
        });

        Schema::table('protocol_activation_histories', function (Blueprint $table) {
            $table->string('invitation_email_status', 32)
                ->nullable()
                ->after('invitation_email_sent_at');
            $table->timestamp('invitation_email_queued_at')
                ->nullable()
                ->after('invitation_email_status');
            $table->timestamp('invitation_email_last_attempt_at')
                ->nullable()
                ->after('invitation_email_queued_at');
            $table->timestamp('invitation_email_failed_at')
                ->nullable()
                ->after('invitation_email_last_attempt_at');
            $table->unsignedTinyInteger('invitation_email_attempts')
                ->default(0)
                ->after('invitation_email_failed_at');
            $table->text('invitation_email_failure_message')
                ->nullable()
                ->after('invitation_email_attempts');

            $table->index('invitation_email_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('protocol_activation_histories', function (Blueprint $table) {
            $table->dropIndex(['invitation_email_status']);
            $table->dropColumn([
                'invitation_email_status',
                'invitation_email_queued_at',
                'invitation_email_last_attempt_at',
                'invitation_email_failed_at',
                'invitation_email_attempts',
                'invitation_email_failure_message',
            ]);
        });

        Schema::table('protocol_lists', function (Blueprint $table) {
            $table->dropIndex(['invitation_email_status']);
            $table->dropIndex(['invitation_email_queued_at']);
            $table->dropIndex(['invitation_email_failed_at']);
            $table->dropColumn([
                'invitation_email_status',
                'invitation_email_queued_at',
                'invitation_email_last_attempt_at',
                'invitation_email_failed_at',
                'invitation_email_attempts',
                'invitation_email_failure_message',
            ]);
        });
    }
};
