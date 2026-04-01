<?php

namespace Tests\Unit;

use App\Models\StudentPartnerProgress;
use App\Models\StudentPartnerProgressHistory;
use App\Services\PartnerProgressVisualizationService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PartnerProgressVisualizationServiceTest extends TestCase
{
    public function test_it_builds_activity_payload_from_snapshot_and_history(): void
    {
        $snapshot = new StudentPartnerProgress([
            'overall_progress_percent' => 42.5,
            'last_synced_at' => now(),
            'stale_after_at' => now()->addDay(),
            'progress_summary_json' => [
                'selected' => [
                    'video_percentage_complete' => 60,
                    'quiz_percentage_complete' => 25,
                    'project_percentage_complete' => 10,
                ],
            ],
        ]);

        $history = new Collection([
            new StudentPartnerProgressHistory([
                'captured_at' => now()->subDay(),
                'overall_progress_percent' => 20,
                'payload_json' => [
                    'selected_metrics' => [
                        'video_percentage_complete' => 20,
                        'quiz_percentage_complete' => 10,
                        'project_percentage_complete' => 5,
                    ],
                ],
            ]),
        ]);

        $service = new PartnerProgressVisualizationService();
        $payload = $service->buildStudentProgressPayload($snapshot, $history);

        $this->assertNotNull($payload['snapshot']);
        $this->assertCount(3, $payload['activities']);
        $this->assertSame('Video', $payload['activities'][0]['label']);
        $this->assertSame(1, count($payload['history']));
    }
}
