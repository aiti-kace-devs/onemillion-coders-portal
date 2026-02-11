<?php

namespace Tests\Feature\Admin;

use App\Models\AdmissionRun;
use App\Models\Batch;
use App\Models\Centre;
use App\Models\Course;
use App\Models\Programme;
use App\Models\Rule;
use App\Models\User;
use App\Models\Admin;
use App\Services\AdmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionRunTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $course;
    protected $batch;
    protected $rule1;
    protected $rule2;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup admin user
        $this->admin = Admin::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);

        // Setup course and dependencies
        $programme = Programme::factory()->create(['title' => 'Test Programme']);
        $centre = Centre::factory()->create();
        $this->course = Course::factory()->create([
            'programme_id' => $programme->id,
            'centre_id' => $centre->id,
            'course_name' => 'Test Course',
        ]);

        $this->batch = Batch::factory()->create(['status' => true]);

        // Setup rules
        $this->rule1 = Rule::factory()->create(['name' => 'Rule 1', 'is_active' => true]);
        $this->rule2 = Rule::factory()->create(['name' => 'Rule 2', 'is_active' => true]);

        // Assign rules to course
        $this->course->rules()->attach($this->rule1->id, ['priority' => 1, 'value' => json_encode([])]);
        $this->course->rules()->attach($this->rule2->id, ['priority' => 2, 'value' => json_encode([])]);
    }

    public function test_get_rules_returns_correct_rules()
    {
        $response = $this->actingAs($this->admin, 'backpack')
            ->getJson(route('admission.get_rules', ['course_id' => $this->course->id]));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'rules')
            ->assertJsonFragment(['id' => $this->rule1->id])
            ->assertJsonFragment(['id' => $this->rule2->id]);
    }

    public function test_preview_admission_uses_active_rules()
    {
        // Mock AdmissionService
        $this->mock(AdmissionService::class, function ($mock) {
            $mock->shouldReceive('previewAdmission')
                ->once()
                ->withArgs(function ($course, $limit, $batchId, $activeRules) {
                    return $course->id === $this->course->id &&
                        $activeRules === [$this->rule1->id];
                })
                ->andReturn([
                    'students' => collect([]),
                    'stats' => [],
                    'rules_applied' => collect([$this->rule1])
                ]);
        });

        $response = $this->actingAs($this->admin, 'backpack')
            ->postJson(route('admission.preview'), [
                'course_id' => $this->course->id,
                'batch_id' => $this->batch->id,
                'limit' => 50,
                'active_rules' => [$this->rule1->id]
            ]);

        $response->assertStatus(200);
    }

    public function test_execute_admission_uses_active_rules()
    {
        // Mock AdmissionService
        $this->mock(AdmissionService::class, function ($mock) {
            $mock->shouldReceive('executeAdmission')
                ->once()
                ->withArgs(function ($course, $limit, $batchId, $sessionId, $admin, $activeRules) {
                    return $course->id === $this->course->id &&
                        $activeRules === [$this->rule2->id];
                })
                ->andReturn(new AdmissionRun([
                    'id' => 1,
                    'admitted_count' => 5,
                    'emailed_count' => 5
                ]));
        });

        $response = $this->actingAs($this->admin, 'backpack')
            ->postJson(route('admission.execute'), [
                'course_id' => $this->course->id,
                'batch_id' => $this->batch->id,
                'limit' => 50,
                'active_rules' => [$this->rule2->id]
            ]);

        $response->assertStatus(200);
    }
}
