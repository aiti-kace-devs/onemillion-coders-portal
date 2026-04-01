<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('partner_progress_sync_audits', function (Blueprint $table) {
            $table->id();
            $table->string('partner_code', 50)->index();
            $table->string('context', 120)->nullable()->index();
            $table->string('omcp_id', 100)->nullable()->index();
            $table->string('reason', 120)->index();
            $table->json('payload_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_progress_sync_audits');
    }
};
