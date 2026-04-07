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
        if (Schema::hasTable('roles') && Schema::hasTable('statamic_roles')) {
            $oldStatamicRoles = DB::table('roles')
                ->whereNotNull('handle')
                ->get();

            foreach ($oldStatamicRoles as $role) {
                if (!DB::table('statamic_roles')->where('handle', $role->handle)->exists()) {
                    DB::table('statamic_roles')->insert([
                        'handle' => $role->handle,
                        'title' => $role->title ?? ucwords(str_replace(['-', '_'], ' ', $role->handle)),
                        'permissions' => $role->permissions,
                        'preferences' => $role->preferences,
                        'created_at' => $role->created_at ?? now(),
                        'updated_at' => $role->updated_at ?? now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        //
    }
};
