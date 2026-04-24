<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait BuildsProtocolTestSchema
{
    protected function bootProtocolTestDatabase(): void
    {
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        foreach ([
            'activity_log',
            'email_templates',
            'otp_verified_emails',
            'protocol_activation_histories',
            'protocol_import_batches',
            'user_exams',
            'oex_question_masters',
            'oex_exam_masters',
            'protocol_lists',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('previous_name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('mobile_no')->nullable();
            $table->string('exam')->nullable();
            $table->boolean('status')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('userId')->nullable();
            $table->string('card_type')->nullable();
            $table->string('ghcard')->nullable()->unique();
            $table->string('gender')->nullable();
            $table->boolean('pwd')->default(false);
            $table->boolean('support')->default(false);
            $table->boolean('is_protocol')->default(false);
            $table->json('data')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('protocol_lists', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('previous_name')->nullable();
            $table->string('email')->unique();
            $table->string('gender');
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('mobile_no');
            $table->string('ghcard')->unique();
            $table->unsignedBigInteger('protocol_import_batch_id')->nullable();
            $table->unsignedTinyInteger('email_change_attempts')->default(0);
            $table->unsignedTinyInteger('ghcard_change_attempts')->default(0);
            $table->timestamp('activation_email_sent_at')->nullable();
            $table->string('invitation_email_status', 32)->default('pending');
            $table->timestamp('invitation_email_queued_at')->nullable();
            $table->timestamp('invitation_email_last_attempt_at')->nullable();
            $table->timestamp('invitation_email_failed_at')->nullable();
            $table->unsignedTinyInteger('invitation_email_attempts')->default(0);
            $table->text('invitation_email_failure_message')->nullable();
            $table->string('invite_token_hash')->nullable()->unique();
            $table->timestamp('invite_token_issued_at')->nullable();
            $table->timestamp('activation_link_opened_at')->nullable();
            $table->string('activation_session_token_hash')->nullable();
            $table->timestamp('activation_session_expires_at')->nullable();
            $table->unsignedTinyInteger('failed_activation_attempts')->default(0);
            $table->timestamps();
        });

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
        });

        Schema::create('protocol_activation_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('protocol_list_id')->nullable();
            $table->unsignedBigInteger('protocol_import_batch_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_uuid')->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('previous_name')->nullable();
            $table->string('email');
            $table->string('gender')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('mobile_no')->nullable();
            $table->string('ghcard');
            $table->string('invite_token_hash')->nullable();
            $table->timestamp('invitation_email_sent_at')->nullable();
            $table->string('invitation_email_status', 32)->nullable();
            $table->timestamp('invitation_email_queued_at')->nullable();
            $table->timestamp('invitation_email_last_attempt_at')->nullable();
            $table->timestamp('invitation_email_failed_at')->nullable();
            $table->unsignedTinyInteger('invitation_email_attempts')->default(0);
            $table->text('invitation_email_failure_message')->nullable();
            $table->timestamp('activation_link_opened_at')->nullable();
            $table->timestamp('activation_completed_at');
            $table->unsignedTinyInteger('failed_activation_attempts')->default(0);
            $table->string('activated_ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('content');
            $table->timestamps();
        });

        Schema::create('otp_verified_emails', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255)->unique();
            $table->string('otp_code_hash', 255)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        DB::table('email_templates')->insert([
            'name' => PROTOCOL_ACTIVATION_INVITATION_EMAIL,
            'content' => <<<'EOD'
## Welcome, {displayName}

[component]: # ('mail::button', ['url' => '{activationUrl}'])
Activate My Account
[endcomponent]: #

[component]: # ('mail::panel')
**National ID:** {ghcard}<br>
**Email:** {email}
[endcomponent]: #
EOD,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('activity_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_type')->nullable();
            $table->string('event')->nullable();
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->string('causer_type')->nullable();
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
            $table->index('log_name');
        });

        Schema::create('oex_exam_masters', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->unsignedBigInteger('category')->nullable();
            $table->integer('passmark')->nullable();
            $table->timestamp('exam_date')->nullable();
            $table->boolean('status')->default(true);
            $table->integer('exam_duration')->nullable();
            $table->integer('number_of_questions')->nullable();
            $table->timestamps();
        });

        Schema::create('oex_question_masters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_id')->nullable();
            $table->timestamps();
        });

        Schema::create('user_exams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('exam_id');
            $table->boolean('std_status')->default(true);
            $table->boolean('exam_joined')->default(false);
            $table->timestamp('started')->nullable();
            $table->timestamp('submitted')->nullable();
            $table->timestamps();
        });
    }
}
