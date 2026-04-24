<?php

namespace Tests\Feature;

use App\Jobs\SendProtocolInvitationEmailJob;
use App\Mail\RenderedTemplateEmail;
use App\Models\ProtocolList;
use App\Services\ProtocolListService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;
use Tests\Concerns\BuildsProtocolTestSchema;

class ProtocolListServiceTest extends TestCase
{
    use BuildsProtocolTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootProtocolTestDatabase();
    }

    public function test_save_rows_dispatches_activation_invitation_job_for_new_participants(): void
    {
        Queue::fake();

        $service = app(ProtocolListService::class);
        $result = $service->saveRows([
            [
                'local_key' => 'row-1',
                'first_name' => 'Yaw',
                'middle_name' => '',
                'last_name' => 'Boateng',
                'previous_name' => '',
                'email' => 'yaw@example.com',
                'gender' => 'male',
                'age' => 26,
                'mobile_no' => '0540000000',
                'ghcard' => 'GHA-111111111-1',
            ],
        ]);

        $this->assertTrue($result['saved']);
        $this->assertDatabaseHas('protocol_lists', [
            'email' => 'yaw@example.com',
            'ghcard' => 'GHA-111111111-1',
        ]);

        $protocol = ProtocolList::query()->where('email', 'yaw@example.com')->firstOrFail();
        $this->assertNotNull($protocol->invite_token_hash);
        $this->assertSame('queued', $protocol->invitation_email_status);
        $this->assertNull($protocol->activation_email_sent_at);
        $this->assertNotNull($protocol->invitation_email_queued_at);

        Queue::assertPushedOn('protocol-emails', SendProtocolInvitationEmailJob::class, function (SendProtocolInvitationEmailJob $job) use ($protocol) {
            return $job->protocolId === $protocol->id
                && ! str_contains($job->inviteToken, 'GHA-111111111-1');
        });
    }

    public function test_save_rows_blocks_third_email_change_attempt(): void
    {
        $protocol = ProtocolList::create([
            'first_name' => 'Akosua',
            'middle_name' => null,
            'last_name' => 'Asante',
            'previous_name' => null,
            'email' => 'akosua@example.com',
            'gender' => 'female',
            'age' => 29,
            'mobile_no' => '0200000000',
            'ghcard' => 'GHA-222222222-2',
            'email_change_attempts' => 2,
        ]);

        $service = app(ProtocolListService::class);
        $result = $service->saveRows([
            [
                'local_key' => 'saved-' . $protocol->id,
                'id' => $protocol->id,
                'first_name' => 'Akosua',
                'middle_name' => '',
                'last_name' => 'Asante',
                'previous_name' => '',
                'email' => 'new-akosua@example.com',
                'gender' => 'female',
                'age' => 29,
                'mobile_no' => '0200000000',
                'ghcard' => 'GHA-222222222-2',
            ],
        ]);

        $this->assertFalse($result['saved']);
        $this->assertSame(
            'Email can only be changed twice for a saved participant.',
            $result['errors'][0]['messages'][0]
        );
    }

    public function test_save_rows_resends_invitation_when_email_and_ghcard_change(): void
    {
        Queue::fake();

        $protocol = ProtocolList::create([
            'first_name' => 'Efua',
            'middle_name' => null,
            'last_name' => 'Arthur',
            'previous_name' => null,
            'email' => 'efua@example.com',
            'gender' => 'female',
            'age' => 24,
            'mobile_no' => '0270000000',
            'ghcard' => 'GHA-333333333-3',
            'email_change_attempts' => 0,
            'ghcard_change_attempts' => 0,
        ]);

        $service = app(ProtocolListService::class);
        $result = $service->saveRows([
            [
                'local_key' => 'saved-' . $protocol->id,
                'id' => $protocol->id,
                'first_name' => 'Efua',
                'middle_name' => '',
                'last_name' => 'Arthur',
                'previous_name' => '',
                'email' => 'efua.updated@example.com',
                'gender' => 'female',
                'age' => 24,
                'mobile_no' => '0270000000',
                'ghcard' => 'GHA-444444444-4',
            ],
        ]);

        $this->assertTrue($result['saved']);
        $this->assertDatabaseHas('protocol_lists', [
            'id' => $protocol->id,
            'email' => 'efua.updated@example.com',
            'ghcard' => 'GHA-444444444-4',
            'email_change_attempts' => 1,
            'ghcard_change_attempts' => 1,
        ]);

        $protocol = ProtocolList::query()->findOrFail($protocol->id);
        $this->assertSame('queued', $protocol->invitation_email_status);
        $this->assertNotNull($protocol->invitation_email_queued_at);

        Queue::assertPushedOn('protocol-emails', SendProtocolInvitationEmailJob::class, function (SendProtocolInvitationEmailJob $job) use ($protocol) {
            return $job->protocolId === $protocol->id;
        });
    }

    public function test_parse_spreadsheet_records_a_server_side_import_batch(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'protocol.csv',
            implode("\n", [
                'first_name,middle_name,last_name,previous_name,email,gender,age,mobile_no,ghcard',
                'Yaw,,Boateng,,yaw@example.com,male,26,0540000000,GHA-111111111-1',
            ])
        );

        $service = app(ProtocolListService::class);
        $result = $service->parseSpreadsheet($file, [
            'id' => 7,
            'name' => 'Portal Admin',
        ]);

        $this->assertCount(1, $result['rows']);
        $this->assertNotNull($result['batch']['id']);

        $this->assertDatabaseHas('protocol_import_batches', [
            'id' => $result['batch']['id'],
            'source_filename' => 'protocol.csv',
            'status' => 'parsed',
            'total_rows' => 1,
            'uploaded_by_admin_id' => 7,
            'uploaded_by_admin_name' => 'Portal Admin',
        ]);
    }

    public function test_parse_spreadsheet_returns_clear_validation_error_when_zip_extension_is_missing_for_xlsx(): void
    {
        if (class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('This environment has the zip extension enabled.');
        }

        $file = UploadedFile::fake()->create('protocol.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $service = app(ProtocolListService::class);

        try {
            $service->parseSpreadsheet($file, [
                'id' => 7,
                'name' => 'Portal Admin',
            ]);
            $this->fail('Expected XLSX parsing to fail when ZipArchive is unavailable.');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            $this->assertSame(
                'XLSX upload is unavailable because the PHP zip extension is not enabled. Enable extension=zip in php.ini or upload the sheet as CSV instead.',
                $errors['file'][0] ?? null
            );
        }
    }

    public function test_import_batch_is_marked_applied_when_imported_rows_are_saved(): void
    {
        Queue::fake();

        $file = UploadedFile::fake()->createWithContent(
            'protocol.csv',
            implode("\n", [
                'first_name,middle_name,last_name,previous_name,email,gender,age,mobile_no,ghcard',
                'Yaw,,Boateng,,yaw@example.com,male,26,0540000000,GHA-111111111-1',
            ])
        );

        $service = app(ProtocolListService::class);
        $parsed = $service->parseSpreadsheet($file, [
            'id' => 3,
            'name' => 'Uploader Admin',
        ]);

        $result = $service->saveRows($parsed['rows'], [
            'id' => 5,
            'name' => 'Approver Admin',
        ]);

        $this->assertTrue($result['saved']);
        $this->assertDatabaseHas('protocol_import_batches', [
            'id' => $parsed['batch']['id'],
            'status' => 'applied',
            'saved_rows' => 1,
            'created_rows' => 1,
            'updated_rows' => 0,
            'invalid_rows' => 0,
            'invitation_emails_sent' => 1,
            'applied_by_admin_id' => 5,
            'applied_by_admin_name' => 'Approver Admin',
        ]);
    }

    public function test_protocol_invitation_job_marks_email_as_sent_when_delivery_succeeds(): void
    {
        Queue::fake();

        $service = app(ProtocolListService::class);
        $result = $service->saveRows([
            [
                'local_key' => 'row-1',
                'first_name' => 'Adwoa',
                'middle_name' => '',
                'last_name' => 'Asiedu',
                'previous_name' => '',
                'email' => 'adwoa@example.com',
                'gender' => 'female',
                'age' => 27,
                'mobile_no' => '0241111111',
                'ghcard' => 'GHA-555555555-5',
            ],
        ]);

        $this->assertTrue($result['saved']);

        $queuedJob = null;
        Queue::assertPushed(SendProtocolInvitationEmailJob::class, function (SendProtocolInvitationEmailJob $job) use (&$queuedJob) {
            $queuedJob = $job;

            return true;
        });

        $this->assertNotNull($queuedJob);

        Mail::fake();
        $queuedJob->handle(app(ProtocolListService::class));

        $protocol = ProtocolList::query()->where('email', 'adwoa@example.com')->firstOrFail();
        $this->assertSame('sent', $protocol->invitation_email_status);
        $this->assertNotNull($protocol->activation_email_sent_at);
        $this->assertNull($protocol->invitation_email_failed_at);
        $this->assertNull($protocol->invitation_email_failure_message);

        Mail::assertSent(RenderedTemplateEmail::class, function (RenderedTemplateEmail $mail) {
            return $mail->subjectLine === 'Welcome to One Million Coders - Activate Your Account';
        });
    }

    public function test_protocol_invitation_job_marks_email_as_failed_when_retries_are_exhausted(): void
    {
        $protocol = ProtocolList::create([
            'first_name' => 'Kwame',
            'middle_name' => null,
            'last_name' => 'Bonsu',
            'previous_name' => null,
            'email' => 'kwame@example.com',
            'gender' => 'male',
            'age' => 30,
            'mobile_no' => '0242222222',
            'ghcard' => 'GHA-666666666-6',
            'invite_token_hash' => hash('sha256', 'public-token'),
            'invitation_email_status' => 'retrying',
            'invitation_email_queued_at' => now(),
        ]);

        $job = new SendProtocolInvitationEmailJob($protocol->id, 'public-token.signature');
        $job->failed(new RuntimeException('SMTP unavailable'));

        $protocol = $protocol->fresh();
        $this->assertSame('failed', $protocol->invitation_email_status);
        $this->assertNotNull($protocol->invitation_email_failed_at);
        $this->assertSame('SMTP unavailable', $protocol->invitation_email_failure_message);
    }
}
