<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "UPDATE centres SET gps_address = CASE\n"
                . "WHEN JSON_TYPE(gps_address) = 'OBJECT' THEN JSON_UNQUOTE(JSON_EXTRACT(gps_address, '$.address'))\n"
                . "WHEN JSON_TYPE(gps_address) = 'STRING' THEN JSON_UNQUOTE(gps_address)\n"
                . "ELSE gps_address END\n"
                . "WHERE JSON_VALID(gps_address)"
            );
        }

        Schema::table('centres', function (Blueprint $table) {
            $table->string('gps_address', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('centres', function (Blueprint $table) {
            $table->json('gps_address')->nullable()->change();
        });
    }
};
