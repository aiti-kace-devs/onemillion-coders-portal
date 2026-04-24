<?php

namespace Tests\Feature;

use App\Events\UserRegistered;
use App\Jobs\SendProtocolInvitationEmailJob;
use App\Models\ProtocolList;
use App\Services\ProtocolListService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Concerns\BuildsProtocolTestSchema;

class ProtocolActivationTest extends TestCase
{
    use BuildsProtocolTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootProtocolTestDatabase();
    }

    public function test_activation_link_can_be_reopened_before_completion(): void
    {
        Queue::fake();

        $service = app(ProtocolListService::class);
        $result = $service->saveRows([
            [
                'local_key' => 'row-1',
                'first_name' => 'Ama',
                'middle_name' => 'K.',
                'last_name' => 'Mensah',
                'previous_name' => '',
                'email' => 'ama@example.com',
                'gender' => 'female',
                'age' => 28,
                'mobile_no' => '0240000000',
                'ghcard' => 'GHA-123456789-0',
            ],
        ]);
        $this->assertTrue($result['saved']);

        $protocol = ProtocolList::query()->where('email', 'ama@example.com')->firstOrFail();
        $inviteToken = $this->queuedInviteTokenFor('ama@example.com');

        $firstResponse = $this->getJson('/api/protocol-activation/' . rawurlencode($inviteToken));
        $firstResponse
            ->assertOk()
            ->assertJsonPath('status', 'ready')
            ->assertJsonStructure(['session_token']);

        $this->assertNotNull($protocol->fresh()->activation_link_opened_at);

        $secondResponse = $this->getJson('/api/protocol-activation/' . rawurlencode($inviteToken));
        $secondResponse
            ->assertOk()
            ->assertJsonPath('status', 'ready')
            ->assertJsonStructure(['session_token']);

        $this->assertNotSame(
            $firstResponse->json('session_token'),
            $secondResponse->json('session_token')
        );
    }

    public function test_protocol_participant_is_moved_into_users_table_on_activation(): void
    {
        Config::set('SEND_SMS_AFTER_REGISTRATION', false);
        Event::fake([UserRegistered::class]);
        Queue::fake();

        $service = app(ProtocolListService::class);
        $result = $service->saveRows([
            [
                'local_key' => 'row-1',
                'first_name' => 'Kojo',
                'middle_name' => 'A.',
                'last_name' => 'Owusu',
                'previous_name' => 'Kojo Mensah',
                'email' => 'kojo@example.com',
                'gender' => 'male',
                'age' => 31,
                'mobile_no' => '0550000000',
                'ghcard' => 'GHA-987654321-0',
            ],
        ]);
        $this->assertTrue($result['saved']);

        $protocol = ProtocolList::query()->where('email', 'kojo@example.com')->firstOrFail();
        $inviteToken = $this->queuedInviteTokenFor('kojo@example.com');

        $sessionResponse = $this->getJson('/api/protocol-activation/' . rawurlencode($inviteToken));
        $sessionResponse->assertOk();
        $sessionToken = $sessionResponse->json('session_token');

        $activateResponse = $this->postJson('/api/protocol-activation/activate', [
            'token' => $inviteToken,
            'session_token' => $sessionToken,
            'national_id' => $protocol->ghcard,
            'password' => 'StrongPass!9',
            'password_confirmation' => 'StrongPass!9',
        ]);

        $activateResponse
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('protocol_lists', [
            'id' => $protocol->id,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'kojo@example.com',
            'ghcard' => 'GHA-987654321-0',
            'is_protocol' => 1,
            'previous_name' => 'Kojo Mensah',
        ]);

        $this->assertDatabaseHas('otp_verified_emails', [
            'email' => 'kojo@example.com',
        ]);

        $otpVerification = \App\Models\OtpVerifiedEmail::query()
            ->where('email', 'kojo@example.com')
            ->first();
        $this->assertNotNull($otpVerification?->verified_at);
        $this->assertNotNull($otpVerification?->used_at);

        $this->assertDatabaseHas('protocol_activation_histories', [
            'email' => 'kojo@example.com',
            'ghcard' => 'GHA-987654321-0',
        ]);

        Event::assertDispatched(UserRegistered::class);
    }

    public function test_activation_link_is_marked_used_after_successful_activation(): void
    {
        Config::set('SEND_SMS_AFTER_REGISTRATION', false);
        Event::fake([UserRegistered::class]);
        Queue::fake();

        $service = app(ProtocolListService::class);
        $result = $service->saveRows([
            [
                'local_key' => 'row-1',
                'first_name' => 'Abena',
                'middle_name' => '',
                'last_name' => 'Adu',
                'previous_name' => '',
                'email' => 'abena@example.com',
                'gender' => 'female',
                'age' => 29,
                'mobile_no' => '0201234567',
                'ghcard' => 'GHA-112233445-6',
            ],
        ]);
        $this->assertTrue($result['saved']);

        $protocol = ProtocolList::query()->where('email', 'abena@example.com')->firstOrFail();
        $inviteToken = $this->queuedInviteTokenFor('abena@example.com');

        $sessionResponse = $this->getJson('/api/protocol-activation/' . rawurlencode($inviteToken));
        $sessionResponse->assertOk();

        $activateResponse = $this->postJson('/api/protocol-activation/activate', [
            'token' => $inviteToken,
            'session_token' => $sessionResponse->json('session_token'),
            'national_id' => $protocol->ghcard,
            'password' => 'StrongPass!9',
            'password_confirmation' => 'StrongPass!9',
        ]);

        $activateResponse
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $reopenResponse = $this->getJson('/api/protocol-activation/' . rawurlencode($inviteToken));
        $reopenResponse
            ->assertStatus(410)
            ->assertJsonPath('status', 'used');
    }

    private function queuedInviteTokenFor(string $email): string
    {
        $inviteToken = null;

        Queue::assertPushed(SendProtocolInvitationEmailJob::class, function (SendProtocolInvitationEmailJob $job) use ($email, &$inviteToken) {
            $protocol = ProtocolList::query()->find($job->protocolId);
            if (! $protocol || $protocol->email !== $email) {
                return false;
            }

            $inviteToken = $job->inviteToken;

            return true;
        });

        $this->assertNotNull($inviteToken);

        return $inviteToken;
    }
}
