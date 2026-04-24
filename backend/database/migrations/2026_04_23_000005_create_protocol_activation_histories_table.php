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
        Schema::create('protocol_activation_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('protocol_list_id')->nullable();
            $table->unsignedBigInteger('protocol_import_batch_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_uuid')->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('previous_name')->nullable();
            $table->string('email');
            $table->string('gender')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('mobile_no')->nullable();
            $table->string('ghcard');
            $table->string('invite_token_hash')->nullable();
            $table->timestamp('invitation_email_sent_at')->nullable();
            $table->timestamp('activation_link_opened_at')->nullable();
            $table->timestamp('activation_completed_at')->nullable();
            $table->unsignedTinyInteger('failed_activation_attempts')->default(0);
            $table->string('activated_ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('protocol_import_batch_id');
            $table->index('user_id');
            $table->index('email');
            $table->index('ghcard');
            $table->index('activation_completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('protocol_activation_histories');
    }
};
