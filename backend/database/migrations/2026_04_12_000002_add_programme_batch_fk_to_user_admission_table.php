<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_admission', function (Blueprint $table) {
            $table->unsignedBigInteger('programme_batch_id')->nullable()->after('course_id');
        });

        Schema::table('user_admission', function (Blueprint $table) {
            $table->foreign('programme_batch_id')
                ->references('id')
                ->on('programme_batches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_admission', function (Blueprint $table) {
            $table->dropForeign(['programme_batch_id']);
            $table->dropColumn('programme_batch_id');
        });
    }
};
