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
            $table->unsignedBigInteger('protocol_import_batch_id')->nullable()->after('ghcard');
            $table->string('invite_token_hash')->nullable()->unique()->after('activation_email_sent_at');
            $table->timestamp('invite_token_issued_at')->nullable()->after('invite_token_hash');

            $table->index('protocol_import_batch_id');
            $table->index('invite_token_issued_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('protocol_lists', function (Blueprint $table) {
            $table->dropIndex(['protocol_import_batch_id']);
            $table->dropIndex(['invite_token_issued_at']);
            $table->dropUnique(['invite_token_hash']);
            $table->dropColumn([
                'protocol_import_batch_id',
                'invite_token_hash',
                'invite_token_issued_at',
            ]);
        });
    }
};
