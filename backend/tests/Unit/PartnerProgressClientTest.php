<?php

namespace Tests\Unit;

use App\Services\Partners\Startocode\PartnerProgressClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PartnerProgressClientTest extends TestCase
{
    public function test_it_fetches_student_progress_successfully(): void
    {
        config()->set('services.partner_startocode.base_url', 'https://example.test');
        config()->set('services.partner_startocode.token', 'token');

        Http::fake([
            'https://example.test/*' => Http::response([
                'status' => 'success',
                'message' => 'Progress fetched successfully',
                'data' => [
                    'omcp_id' => 'omcp-10001',
                    'progress' => [
                        'learning_paths' => [],
                        'courses' => [],
                    ],
                ],
            ], 200),
        ]);

        $client = new PartnerProgressClient();
        $result = $client->fetchStudentProgress('omcp-10001');

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
        $this->assertSame('Progress fetched successfully', $result['message']);
    }

    public function test_it_returns_non_retryable_for_422_errors(): void
    {
        config()->set('services.partner_startocode.base_url', 'https://example.test');
        config()->set('services.partner_startocode.token', 'token');

        Http::fake([
            'https://example.test/*' => Http::response([
                'status' => 'error',
                'message' => 'Invalid updated_since',
            ], 422),
        ]);

        $client = new PartnerProgressClient();
        $result = $client->fetchStudentProgress('omcp-10001');

        $this->assertFalse($result['ok']);
        $this->assertSame(422, $result['status']);
        $this->assertFalse($result['retryable']);
    }

    public function test_it_fetches_program_progress_page_with_pagination(): void
    {
        config()->set('services.partner_startocode.base_url', 'https://example.test');
        config()->set('services.partner_startocode.token', 'token');

        Http::fake([
            'https://example.test/*' => Http::response([
                'status' => 'success',
                'message' => 'ok',
                'data' => [
                    'items' => [
                        ['omcp_id' => 'omcp-10001', 'progress' => []],
                    ],
                ],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 3,
                    'total' => 10,
                ],
            ], 200),
        ]);

        $client = new PartnerProgressClient();
        $result = $client->fetchProgramProgressPage('gh-program', 1, 100);

        $this->assertTrue($result['ok']);
        $this->assertCount(1, $result['items']);
        $this->assertTrue($result['pagination']['has_more']);
        $this->assertSame(3, $result['pagination']['last_page']);
    }
}
