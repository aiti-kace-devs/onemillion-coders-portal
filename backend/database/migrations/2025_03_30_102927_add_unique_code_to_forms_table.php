<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            //
            $table->string('code')->unique(true)->default(Str::random(6));
            $table->string('message_after_registration')->default('Thank you for your submission');
            $table->string('message_when_inactive')->default('The form is not accepting submissions at this moment');
            $table->tinyInteger('active')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('code');
            $table->dropColumn('message_after_registration');
            $table->dropColumn('message_when_inactive');
            $table->dropColumn('active');
        });
    }
};
