<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('master_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('master_name');
            $table->string('session_type'); // Morning, Afternoon, Evening, Fullday, Online
            $table->string('time');
            $table->string('course_type')->default('standard');
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index('status');
            $table->index('session_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_sessions');
    }
};
