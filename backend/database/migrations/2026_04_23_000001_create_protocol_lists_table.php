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
        Schema::create('protocol_lists', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('previous_name')->nullable();
            $table->string('email')->unique();
            $table->string('gender');
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('mobile_no');
            $table->string('ghcard')->unique();
            $table->unsignedTinyInteger('email_change_attempts')->default(0);
            $table->unsignedTinyInteger('ghcard_change_attempts')->default(0);
            $table->timestamp('activation_email_sent_at')->nullable();
            $table->timestamp('activation_link_opened_at')->nullable();
            $table->string('activation_session_token_hash')->nullable();
            $table->timestamp('activation_session_expires_at')->nullable();
            $table->unsignedTinyInteger('failed_activation_attempts')->default(0);
            $table->timestamps();

            $table->index('activation_link_opened_at');
            $table->index('activation_session_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('protocol_lists');
    }
};
