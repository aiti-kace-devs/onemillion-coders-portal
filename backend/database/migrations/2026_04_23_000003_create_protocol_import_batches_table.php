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
        Schema::create('protocol_import_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_uuid')->unique();
            $table->string('source_filename');
            $table->string('source_extension', 16)->nullable();
            $table->unsignedBigInteger('uploaded_by_admin_id')->nullable();
            $table->string('uploaded_by_admin_name')->nullable();
            $table->unsignedBigInteger('applied_by_admin_id')->nullable();
            $table->string('applied_by_admin_name')->nullable();
            $table->string('status', 32)->default('parsed');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('saved_rows')->default(0);
            $table->unsignedInteger('created_rows')->default(0);
            $table->unsignedInteger('updated_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->unsignedInteger('invitation_emails_sent')->default(0);
            $table->json('rows_snapshot')->nullable();
            $table->json('error_snapshot')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('uploaded_at');
            $table->index('applied_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('protocol_import_batches');
    }
};
