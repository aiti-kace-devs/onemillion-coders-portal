<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('partner_integrations')) {
            return;
        }

        Schema::table('partner_integrations', function (Blueprint $table) {
            if (!Schema::hasColumn('partner_integrations', 'endpoints_json')) {
                $table->json('endpoints_json')->nullable()->after('signature_config_json');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('partner_integrations')) {
            return;
        }

        Schema::table('partner_integrations', function (Blueprint $table) {
            if (Schema::hasColumn('partner_integrations', 'endpoints_json')) {
                $table->dropColumn('endpoints_json');
            }
        });
    }
};
