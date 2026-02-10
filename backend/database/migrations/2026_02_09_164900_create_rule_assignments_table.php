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
        Schema::create('rule_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('rules')->onDelete('cascade');
            $table->string('ruleable_type');
            $table->unsignedBigInteger('ruleable_id');
            $table->json('value')->nullable();
            $table->integer('priority')->default(0);
            $table->timestamps();
            
            $table->index(['ruleable_type', 'ruleable_id']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rule_assignments');
    }
};
