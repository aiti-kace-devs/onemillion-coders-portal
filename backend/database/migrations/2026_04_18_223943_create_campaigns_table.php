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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('message');
            $table->string('priority')->default('normal');
            $table->string('type')->default('campaign');
            $table->json('target_branches')->nullable(); // array of branch_ids
            $table->json('target_districts')->nullable(); // array of district_ids
            $table->json('target_centres')->nullable(); // array of centre_ids
            $table->json('target_programme_batches')->nullable(); // array of programme_batch_ids
            $table->json('target_master_sessions')->nullable(); // array of master_session_ids
            $table->json('target_course_sessions')->nullable(); // array of course_session_ids
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); // admin_id
            $table->foreign('created_by')->references('id')->on('admins')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
