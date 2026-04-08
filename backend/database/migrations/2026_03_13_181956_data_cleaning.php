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
        Schema::table('user_admission', function (Blueprint $table) {
            if (Schema::hasColumn('user_admission', 'batch_id')) {
                $table->dropForeign(['batch_id']);
            }
            $table->dropColumn(['submitted', 'location', 'status', 'batch_id']);
        });

        Schema::table('admission_batches', function (Blueprint $table) {
            if (Schema::hasColumn('admission_batches', 'course_id')) {
                $table->dropForeign(['course_id']);
            }
            $table->dropColumn(['total_admitted_students', 'total_completed_students', 'course_id']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->tinyInteger('status')->nullable()->change();
        });

        Schema::table('centres', function (Blueprint $table) {
            $table->tinyInteger('status')->nullable()->change();
        });

        Schema::table('course_sessions', function (Blueprint $table) {
            $table->tinyInteger('status')->nullable()->change();
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->tinyInteger('status')->nullable()->change();
            $table->dropColumn('location');
            $table->dropColumn('course');
        });

        Schema::table('oex_categories', function (Blueprint $table) {
            $table->tinyInteger('status')->default(1)->change();
        });

        Schema::table('oex_exam_masters', function (Blueprint $table) {
            $table->tinyInteger('passmark')->nullable()->change();
            $table->tinyInteger('exam_duration')->nullable()->change();
            $table->tinyInteger('status')->nullable()->change();
            $table->dateTime('exam_date')->nullable()->change();
        });

        Schema::table('oex_question_masters', function (Blueprint $table) {
            $table->tinyInteger('status')->nullable()->change();
        });

        Schema::table('user_exams', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->change();
            $table->unsignedBigInteger('exam_id')->change();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('exam_id')->references('id')->on('oex_exam_masters')->restrictOnDelete()->cascadeOnUpdate();
            $table->tinyInteger('std_status')->change();
            $table->tinyInteger('exam_joined')->change();
            $table->dateTime('user_feedback')->nullable()->change();
        });

        Schema::table('programmes', function (Blueprint $table) {
            $table->tinyInteger('status')->nullable()->change();
            $table->string('description')->nullable()->change();
            $table->string('sub_title')->nullable()->change();
            $table->string('level')->nullable()->change();
            $table->string('image')->nullable()->change();
            $table->json('overview')->nullable()->change();
            $table->dropColumn('cover_image_id');
        });

        if (!Schema::hasColumn('course_categories', 'icon')) {
            Schema::table('course_categories', function (Blueprint $table) {
                $table->string('icon')->nullable()->after('title');
            });
        }

        Schema::dropIfExists('pagebuilder__page_translations');
        Schema::dropIfExists('pagebuilder__pages');
        Schema::dropIfExists('pagebuilder__settings');
        Schema::dropIfExists('pagebuilder__uploads');
        Schema::dropIfExists('runway_uris');
        Schema::dropIfExists('periods');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'form_response_id')) {
                $table->dropForeign(['form_response_id']);
            }
            $table->dropColumn(['email_verified_at', 'contact', 'form_response_id']);
            $table->tinyInteger('shortlist')->nullable()->change();
            $table->tinyInteger('status')->nullable()->change();
            $table->renameColumn('has_disability', 'pwd');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('verified_by')->nullable()->change();
            $table->foreign('verified_by')->references('id')->on('admins')->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::dropIfExists('form_responses');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('status')->nullable()->change();
            $table->string('location')->nullable();
            $table->string('course')->nullable();
        });

        Schema::table('course_sessions', function (Blueprint $table) {
            $table->string('status')->nullable()->change();
        });

        Schema::table('centres', function (Blueprint $table) {
            $table->string('status')->nullable()->change();
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->string('status')->nullable()->change();
        });

        Schema::table('oex_categories', function (Blueprint $table) {
            $table->integer('status')->nullable()->change();
        });

        Schema::table('oex_exam_masters', function (Blueprint $table) {
            $table->string('passmark')->nullable()->change();
            $table->string('exam_duration')->nullable()->change();
            $table->string('status')->nullable()->change();
            $table->string('exam_date')->nullable()->change();
        });

        Schema::table('oex_question_masters', function (Blueprint $table) {
            $table->string('status')->nullable()->change();
        });

        Schema::table('user_exams', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['exam_id']);
            $table->string('user_id')->change();
            $table->string('exam_id')->change();
            $table->string('std_status')->change();
            $table->string('exam_joined')->change();
            $table->string('user_feedback')->nullable()->change();
        });

        Schema::table('programmes', function (Blueprint $table) {
            $table->string('status')->nullable()->change();
            $table->text('description')->nullable()->change();
            $table->text('sub_title')->nullable()->change();
            $table->text('level')->nullable()->change();
            $table->text('image')->nullable()->change();
            $table->text('overview')->nullable()->change();
            $table->unsignedBigInteger('cover_image_id')->nullable();
        });

        Schema::table('course_categories', function (Blueprint $table) {
            $table->dropColumn('icon');
        });

        Schema::table('admission_batches', function (Blueprint $table) {
            $table->integer('total_admitted_students')->nullable();
            $table->integer('total_completed_students')->nullable();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
        });

        Schema::table('user_admission', function (Blueprint $table) {
            $table->dateTime('submitted')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('batch_id')->nullable()->constrained('admission_batches')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->string('verified_by')->nullable()->change();
            $table->integer('shortlist')->nullable()->change();
            $table->string('status')->nullable()->change();
            $table->renameColumn('pwd', 'has_disability');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('contact')->nullable();
            $table->foreignId('form_response_id')->nullable()->constrained('form_responses')->nullOnDelete();
        });

        Schema::create('form_responses', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('form_id')->constrained()->onDelete('cascade');
            $table->JSON('response_data');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::create('periods', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('runway_uris', function (Blueprint $table) {
            $table->id();
            $table->string('uri');
            $table->string('model_type');
            $table->string('model_id', 36);
            $table->timestamps();
        });

        Schema::create('pagebuilder__pages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 256);
            $table->string('layout', 256);
            $table->longText('data')->nullable();
            $table->timestamps();
        });

        Schema::create('pagebuilder__page_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('page_id');
            $table->string('locale', 50);
            $table->string('title');
            $table->string('meta_title');
            $table->string('meta_description');
            $table->string('route');
            $table->timestamps();
            $table->unique(['page_id', 'locale']);
            $table->foreign('page_id')->references('id')->on('pagebuilder__pages')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::create('pagebuilder__settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('setting', 50)->unique();
            $table->mediumText('value');
            $table->boolean('is_array');
            $table->timestamps();
        });

        Schema::create('pagebuilder__uploads', function (Blueprint $table) {
            $table->increments('id');
            $table->string('public_id', 50)->unique();
            $table->string('original_file', 512);
            $table->string('mime_type', 50);
            $table->string('server_file', 512)->unique();
            $table->timestamps();
        });
    }
};
