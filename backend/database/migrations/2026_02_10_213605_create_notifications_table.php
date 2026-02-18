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
        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();                                    // id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY

            $table->unsignedBigInteger('user_id');          // STUDENT ID - Foreign key to users

            $table->string('type');                          // NOTIFICATION TYPE (registration_email, aptitude_test_email, etc)

            $table->string('title');                         // EMAIL SUBJECT

            $table->longText('message');                     // EMAIL CONTENT/BODY

            $table->text('description')->nullable();         // Additional details

            $table->string('priority')->default('normal');   // Priority level

            $table->timestamp('read_at')->nullable();        // STATUS: NULL = UNREAD, has timestamp = READ

            $table->timestamps();                            // created_at and updated_at

            // Foreign key constraint
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');                     // Delete notifications when user is deleted

            // Indexes for fast queries
            $table->index(['user_id', 'read_at']);           // For filtering unread notifications per user
            $table->index('created_at');                     // For sorting by date
            $table->index('type');                           // For filtering by notification type
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
