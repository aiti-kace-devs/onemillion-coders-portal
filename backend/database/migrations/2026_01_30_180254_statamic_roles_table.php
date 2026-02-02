<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class StatamicRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('name')->nullable()->unique()->change();
            $table->string('guard_name')->default(config('auth.defaults.guard', 'admin'))->change();

            $table->string('handle')->nullable()->unique();
            $table->string('title')->nullable();
            $table->json('permissions')->nullable();
            $table->json('preferences')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Schema::dropIfExists('roles');
    }
}
