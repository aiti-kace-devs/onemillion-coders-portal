<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('maintenance_alerts')) {
            return;
        }

        Schema::create('maintenance_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('type')->default('system');
            $table->string('severity')->default('warning');
            $table->string('status')->default('pending');
            $table->string('title');
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('detected_at')->nullable();
            $table->timestamp('action_due_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by_admin_id')->nullable();
            $table->timestamps();

            $table->index(['status', 'action_due_at']);
            $table->index('detected_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_alerts');
    }
};
