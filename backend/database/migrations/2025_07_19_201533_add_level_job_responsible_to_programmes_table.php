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
        Schema::table('programmes', function (Blueprint $table) {
            $table->text('level')->nullable()->after('sub_title');
            $table->text('job_responsible')->nullable()->after('level');
            $table->text('image')->nullable()->after('job_responsible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programmes', function (Blueprint $table) {
            $table->dropColumn([
                'level',
                'job_responsible',
                'image'
            ]);
        });
    }
};
