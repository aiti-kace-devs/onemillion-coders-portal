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
            if (!Schema::hasColumn('partner_integrations', 'path_param_bindings_json')) {
                $table->json('path_param_bindings_json')->nullable()->after('endpoints_json');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('partner_integrations')) {
            return;
        }

        Schema::table('partner_integrations', function (Blueprint $table) {
            if (Schema::hasColumn('partner_integrations', 'path_param_bindings_json')) {
                $table->dropColumn('path_param_bindings_json');
            }
        });
    }
};
