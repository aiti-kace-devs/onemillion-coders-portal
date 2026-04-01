<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PartnerProgressSyncService;
use App\Services\PartnerProgressVisualizationService;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;
use Mockery;
use Tests\TestCase;

class StudentProgressPageTest extends TestCase
{
    public function test_it_redirects_student_when_not_eligible_for_progress(): void
    {
        if (!Schema::hasTable('users')) {
            $this->markTestSkipped('users table not available in current test database.');
        }

        $user = User::factory()->create();

        $syncService = Mockery::mock(PartnerProgressSyncService::class);
        $syncService->shouldReceive('getSnapshotForPreview')->once()->andReturn([
            'eligible' => false,
            'snapshot' => null,
            'status' => 'not_eligible',
            'course_id' => null,
        ]);
        $this->app->instance(PartnerProgressSyncService::class, $syncService);

        $response = $this->actingAs($user, 'web')->call('GET', 'http://localhost/student/progress');

        $response->assertRedirect('/student/dashboard');
    }

    public function test_it_renders_student_progress_page_when_eligible(): void
    {
        if (!Schema::hasTable('users')) {
            $this->markTestSkipped('users table not available in current test database.');
        }

        $user = User::factory()->create();

        $syncService = Mockery::mock(PartnerProgressSyncService::class);
        $syncService->shouldReceive('getSnapshotForPreview')->once()->andReturn([
            'eligible' => true,
            'snapshot' => null,
            'status' => 'ready',
            'course_id' => null,
        ]);
        $this->app->instance(PartnerProgressSyncService::class, $syncService);

        $vizService = Mockery::mock(PartnerProgressVisualizationService::class);
        $vizService->shouldReceive('buildStudentProgressPayload')->once()->andReturn([
            'snapshot' => null,
            'activities' => [],
            'history' => [],
        ]);
        $this->app->instance(PartnerProgressVisualizationService::class, $vizService);

        $response = $this->actingAs($user, 'web')->call('GET', 'http://localhost/student/progress');

        $response->assertStatus(200);
        $response->assertInertia(fn(AssertableInertia $page) => $page
            ->component('Student/Progress')
            ->where('progressState.eligible', true)
        );
    }
}
