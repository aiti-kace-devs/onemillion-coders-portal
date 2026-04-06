<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('roles')) {
            if (Schema::hasColumn('roles', 'handle')) {
                DB::table('roles')->whereNotNull('handle')->delete();
            }

            Schema::table('roles', function (Blueprint $table) {
                $columnsToDrop = ['handle', 'title', 'permissions', 'preferences'];
                foreach ($columnsToDrop as $column) {
                    if (Schema::hasColumn('roles', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('handle')->nullable()->unique();
                $table->string('title')->nullable();
                $table->json('permissions')->nullable();
                $table->json('preferences')->nullable();
            });
        }
    }
};
