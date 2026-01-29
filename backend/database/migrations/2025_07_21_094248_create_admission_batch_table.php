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
        Schema::create('admission_batches', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('total_admitted_students')->nullable();
            $table->integer('total_completed_students')->nullable();
            $table->string('year')->nullable();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->boolean('completed')->default(true);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });


        Schema::table('user_admission', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->constrained('admission_batches')->nullOnDelete()->after('course_id');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->constrained('admission_batches')->nullOnDelete()->after('course');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('student_id')->unique()->nullable()->after('gender');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_batches');

        Schema::table('user_admission', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
            $table->dropColumn([
                'batch_id'
            ]);
        });


        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
            $table->dropColumn([
                'batch_id'
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'student_id'
            ]);
        });
    }
};
