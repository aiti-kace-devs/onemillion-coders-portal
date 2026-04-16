<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ghana_card_verifications', function (Blueprint $table) {
            $table->text('status_message')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ghana_card_verifications', function (Blueprint $table) {
            $table->string('status_message')->nullable()->change();
        });
    }
};

