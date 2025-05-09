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
        Schema::create('flag_students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('gender');
            $table->string('mobile_no');
            $table->string('age');
            $table->unsignedBigInteger('registered_course');
            $table->string('userId');
            $table->unsignedBigInteger('flag_course');
            $table->datetime('created_at')->useCurrent();

            $table->index('userId');
            $table->index('registered_course');
            $table->index('flag_course');
            $table->index('created_at');

            // foreign keys
            $table->foreign('userId')
                ->references('userId')
                ->on('users')->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('registered_course')
                ->references('id')
                ->on('courses')->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('flag_course')
                ->references('id')
                ->on('courses')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flag_students');
    }
};
