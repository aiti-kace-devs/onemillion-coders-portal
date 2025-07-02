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
        Schema::table('oex_exam_masters', function (Blueprint $table) {
            $table->unsignedBigInteger('category')->change();
            $table->unsignedBigInteger('id')->change();

            // index
            $table->unique('title');

            // foreign keys
            $table->foreign('category')->references('id')->on('oex_categories')
                ->onDelete('restrict')->onUpdate('cascade');
        });

        Schema::table('courses', function (Blueprint $table) {
            // index
            $table->index('id');
            $table->unique('course_name');
            $table->unique([
                'centre_id',
                'programme_id'
            ]);

            // foreign keys
            $table->foreign('centre_id')->references('id')->on('centres')
                ->onDelete('restrict')->onUpdate('cascade');

            $table->foreign('programme_id')->references('id')->on('programmes')
                ->onDelete('restrict')->onUpdate('cascade');
        });

        Schema::table('users', function (Blueprint $table) {
            // column type changes
            $table->unsignedBigInteger('exam')->change();

            // index
            $table->index('id');
            $table->unique('userId');


            // foreign keys
            $table->foreign('exam')->references('id')->on('oex_exam_masters')
                ->onDelete('restrict')->onUpdate('cascade');

            $table->foreign('registered_course')->references('id')->on('courses')
                ->onDelete('restrict')->onUpdate('cascade');
        });

        Schema::table('admins', function (Blueprint $table) {
            // index
        });

        Schema::table('attendances', function (Blueprint $table) {
            // column type changes
            $table->unsignedBigInteger('course_id')->change();

            // foreign keys
            $table->foreign('user_id')->references('userId')->on('users')
                ->onDelete('restrict')->onUpdate('cascade');

            $table->foreign('course_id')->references('id')->on('courses')
                ->onDelete('restrict')->onUpdate('cascade');
        });

        Schema::table('branches', function (Blueprint $table) {
            // index
            $table->unique('title');
        });

        Schema::table('centres', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->change();

            // index
            $table->unique('title');

            // foreign keys
            $table->foreign('branch_id')->references('id')->on('branches')
                ->onDelete('restrict')->onUpdate('cascade');
        });

        Schema::table('course_sessions', function (Blueprint $table) {

            $table->unsignedBigInteger('course_id')->change();

            // index
            $table->index(['course_id', 'session']);
            $table->unique('session');

            // foreign keys
            $table->foreign('course_id')->references('id')->on('courses')
                ->onDelete('restrict')->onUpdate('cascade');
        });

        Schema::table('oex_categories', function (Blueprint $table) {
            // index
            $table->index('id');
            $table->unique('name');
        });

        Schema::table('oex_question_masters', function (Blueprint $table) {
            $table->renameColumn('exam_id', 'exam_set_id');
            // $table->unsignedBigInteger('exam_id')->change();
        });

        Schema::table('oex_question_masters', function (Blueprint $table) {
            $table->unsignedBigInteger('exam_id')->default(1);

            // foreign keys
            $table->foreign('exam_id')->references('id')->on('oex_exam_masters')
                ->onDelete('restrict')->onUpdate('cascade');
        });

        Schema::table('oex_results', function (Blueprint $table) {
            $table->unsignedBigInteger('exam_id')->change();
            $table->unsignedBigInteger('user_id')->change();
            $table->integer('yes_ans')->change();
            $table->integer('no_ans')->change();


            // index
            $table->index(['exam_id', 'user_id']);
            $table->index('yes_ans');
            $table->index('no_ans');
            $table->index('created_at');
            $table->index('id');


            // foreign keys
            $table->foreign('exam_id')->references('id')->on('oex_exam_masters')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')
                ->onDelete('restrict')->onUpdate('cascade');
        });


        Schema::table('programmes', function (Blueprint $table) {
            $table->unique('title');
        });



        Schema::table('user_admission', function (Blueprint $table) {
            // column type changes
            $table->unsignedBigInteger('course_id')->change();
            $table->unsignedBigInteger('session')->change();


            // index
            $table->unique('user_id');
            $table->index(['user_id', 'course_id']);
            $table->index('email_sent');
            $table->index('submitted');
            $table->index('confirmed');

            // foreign keys
            $table->foreign('session')->references('id')->on('course_sessions')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('course_id')->references('id')->on('courses')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('user_id')->references('userId')->on('users')
                ->onDelete('restrict')->onUpdate('cascade');
        });

        Schema::table('user_exams', function (Blueprint $table) {
            // column type changes
            $table->unsignedBigInteger('user_id')->change();
            $table->unsignedBigInteger('exam_id')->change();

            // index
            $table->index('user_id');
            $table->index('exam_id');
            $table->index(['user_id', 'exam_id',]);
            $table->index('exam_joined');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
