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
        Schema::create('ghana_card_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('pin_number');
            $table->string('transaction_guid')->nullable();
            $table->boolean('success')->default(false);
            $table->boolean('verified')->default(false);
            $table->string('code')->nullable();
            $table->json('person_data')->nullable();
            $table->timestamp('request_timestamp')->nullable();
            $table->timestamp('response_timestamp')->nullable();
            $table->string('status_message')->nullable();
            $table->timestamps();

            $table->index(['pin_number', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_verification_blocked')->default(false)->after('ghcard');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_verification_blocked');
        });

        Schema::dropIfExists('ghana_card_verifications');
    }
};
