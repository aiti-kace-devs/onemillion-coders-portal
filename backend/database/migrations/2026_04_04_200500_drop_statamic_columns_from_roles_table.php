<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropStatamicColumnsFromRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                $columns = ['handle', 'title', 'permissions', 'preferences'];
                foreach ($columns as $column) {
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
                $table->string('name')->nullable()->change();
                $table->string('guard_name')->nullable()->change();

                $table->string('handle')->nullable()->unique();
                $table->string('title')->nullable();
                $table->json('permissions')->nullable();
                $table->json('preferences')->nullable();
            });
        }
    }
}
