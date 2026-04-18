<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['master_session_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('master_session_id')
                ->nullable()
                ->change();
            $table->foreign('master_session_id')
                ->references('id')
                ->on('master_sessions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['master_session_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('master_session_id')
                ->nullable(false)
                ->change();
            $table->foreign('master_session_id')
                ->references('id')
                ->on('master_sessions')
                ->cascadeOnDelete();
        });
    }
};
