<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('partner_code', 64)->unique();
            $table->string('display_name', 120);
            $table->boolean('is_enabled')->default(true)->index();

            $table->string('base_url')->nullable();
            $table->string('auth_type', 40)->default('none'); // none|bearer_token|api_key_header|basic|custom
            $table->json('auth_config_json')->nullable();
            $table->json('headers_json')->nullable();
            $table->json('signature_config_json')->nullable();

            $table->unsignedInteger('rate_limit_per_minute')->nullable();
            $table->unsignedInteger('timeout_seconds')->nullable();
            $table->unsignedTinyInteger('retry_attempts')->nullable();
            $table->json('retry_backoff_ms_json')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_integrations');
    }
};

