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
        Schema::table('courses', function (Blueprint $table) {
            $table->date('auto_admit_on')->nullable()->after('status');
            $table->integer('auto_admit_limit')->nullable()->after('auto_admit_on');
            $table->boolean('auto_admit_enabled')->default(false)->after('auto_admit_limit');
            $table->timestamp('last_auto_admit_at')->nullable()->after('auto_admit_enabled');
            
            $table->index(['auto_admit_on', 'auto_admit_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['auto_admit_on', 'auto_admit_enabled']);
            $table->dropColumn(['auto_admit_on', 'auto_admit_limit', 'auto_admit_enabled', 'last_auto_admit_at']);
        });
    }
};
