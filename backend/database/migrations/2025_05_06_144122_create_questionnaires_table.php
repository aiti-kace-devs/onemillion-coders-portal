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
        Schema::create('questionnaires', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('code')->unique(true)->default(\Str::random(6));
            $table->string('image')->nullable();
            $table->JSON('schema');
            $table->string('message_after_submission')->default('Thank you for your submission');
            $table->string('message_when_inactive')->default('The form is not accepting submissions at this moment');
            $table->tinyInteger('active')->default(1);
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questionnaires');
    }
};
