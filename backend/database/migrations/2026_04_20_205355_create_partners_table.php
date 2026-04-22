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
        Schema::create('partners', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('name');
            $blueprint->string('slug')->unique();
            $blueprint->text('api_credentials')->nullable(); // Should be cast to encrypted:array in model
            $blueprint->boolean('active')->default(true);
            $blueprint->timestamps();
        });

        Schema::table('programmes', function (Blueprint $table) {
            $table->foreignId('partner_id')->nullable()->after('provider')->constrained('partners')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programmes', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
            $table->dropColumn('partner_id');
        });
        Schema::dropIfExists('partners');
    }
};
