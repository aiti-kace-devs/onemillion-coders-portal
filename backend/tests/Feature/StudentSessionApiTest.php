<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Branch;
use App\Models\Centre;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Programme;
use App\Models\User;
use App\Models\UserAdmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentSessionApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedInPersonCourseWithSession(User $user, bool $userNeedsSupport = false): array
    {
        $user->support = $userNeedsSupport;
        $user->save();

        $branch = Branch::query()->create([
            'title' => 'Test Region',
            'status' => true,
        ]);

        $centre = new Centre([
            'title' => 'Test Centre',
            'branch_id' => $branch->id,
            'status' => true,
        ]);
        $centre->save();

        $programme = Programme::query()->create([
            'title' => 'Test Programme',
            'mode_of_delivery' => 'In person',
            'status' => true,
        ]);

        $batch = Batch::query()->create([
            'title' => 'Test Batch',
            'status' => true,
        ]);

        $course = Course::query()->create([
            'centre_id' => $centre->id,
            'programme_id' => $programme->id,
            'batch_id' => $batch->id,
            'course_name' => 'Test Programme - (Test Centre)',
            'status' => true,
        ]);

        $session = CourseSession::query()->create([
            'name' => 'Test Session',
            'course_id' => $course->id,
            'limit' => 10,
            'course_time' => '09:00',
            'session' => 'Morning',
            'status' => true,
            'session_type' => CourseSession::TYPE_COURSE,
        ]);

        UserAdmission::query()->create([
            'user_id' => $user->userId,
            'course_id' => (string) $course->id,
            'session' => null,
            'confirmed' => null,
        ]);

        return compact('course', 'session', 'programme', 'centre', 'batch');
    }

    private function seedOnlineCourseWithSession(User $user, bool $userNeedsSupport = false): array
    {
        $user->support = $userNeedsSupport;
        $user->save();

        $branch = Branch::query()->create([
            'title' => 'Test Region',
            'status' => true,
        ]);

        $centre = new Centre([
            'title' => 'Test Centre',
            'branch_id' => $branch->id,
            'status' => true,
        ]);
        $centre->save();

        $programme = Programme::query()->create([
            'title' => 'Online Programme',
            'mode_of_delivery' => 'online',
            'status' => true,
        ]);

        $batch = Batch::query()->create([
            'title' => 'Online Batch',
            'status' => true,
        ]);

        $course = Course::query()->create([
            'centre_id' => $centre->id,
            'programme_id' => $programme->id,
            'batch_id' => $batch->id,
            'course_name' => 'Online Programme - (Test Centre)',
            'status' => true,
        ]);

        $session = CourseSession::query()->create([
            'name' => 'Online Test Session',
            'course_id' => $course->id,
            'limit' => 10,
            'course_time' => '09:00',
            'session' => 'Morning',
            'status' => true,
            'session_type' => CourseSession::TYPE_COURSE,
        ]);

        UserAdmission::query()->create([
            'user_id' => $user->userId,
            'course_id' => (string) $course->id,
            'session' => null,
            'confirmed' => null,
        ]);

        return compact('course', 'session', 'programme', 'centre', 'batch');
    }

    public function test_session_options_requires_authentication(): void
    {
        $this->getJson('/api/v1/student/session-options')
            ->assertStatus(401);
    }

    public function test_session_confirm_requires_authentication(): void
    {
        $this->postJson('/api/v1/student/session-confirm', ['session_id' => 1])
            ->assertStatus(401);
    }

    public function test_session_options_404_when_student_has_no_admission(): void
    {
        $user = User::factory()->create([
            'userId' => (string) Str::uuid(),
            'support' => false,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/student/session-options')
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'no_admission');
    }

    public function test_session_options_returns_simple_flow_when_student_does_not_need_support(): void
    {
        $user = User::factory()->create([
            'userId' => (string) Str::uuid(),
            'support' => false,
        ]);

        $this->seedInPersonCourseWithSession($user, false);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/student/session-options');
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.flow', 'simple')
            ->assertJsonPath('data.attendance_mode', 'centre_based')
            ->assertJsonPath('data.alternatives.same_centre_other_courses', [])
            ->assertJsonPath('data.alternatives.same_course_other_centres', []);

        $this->assertNotEmpty($response->json('data.course_sessions'));
    }

    public function test_session_options_returns_centre_support_flow_when_in_person_and_support_true(): void
    {
        $user = User::factory()->create([
            'userId' => (string) Str::uuid(),
            'support' => true,
        ]);

        $this->seedInPersonCourseWithSession($user, true);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/student/session-options')
            ->assertOk()
            ->assertJsonPath('data.flow', 'centre_support')
            ->assertJsonPath('data.attendance_mode', 'centre_based');
    }

    public function test_session_options_online_with_support_true_uses_centre_support_flow(): void
    {
        $user = User::factory()->create([
            'userId' => (string) Str::uuid(),
            'support' => true,
        ]);

        $this->seedOnlineCourseWithSession($user, true);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/student/session-options')
            ->assertOk()
            ->assertJsonPath('data.flow', 'centre_support')
            ->assertJsonPath('data.attendance_mode', 'centre_based');
    }

    public function test_session_options_online_without_support_is_simple_and_fully_remote(): void
    {
        $user = User::factory()->create([
            'userId' => (string) Str::uuid(),
            'support' => false,
        ]);

        $this->seedOnlineCourseWithSession($user, false);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/student/session-options')
            ->assertOk()
            ->assertJsonPath('data.flow', 'simple')
            ->assertJsonPath('data.attendance_mode', 'fully_remote')
            ->assertJsonPath('data.alternatives.same_centre_other_courses', [])
            ->assertJsonPath('data.alternatives.same_course_other_centres', []);
    }

    public function test_session_confirm_succeeds_for_valid_session(): void
    {
        $user = User::factory()->create([
            'userId' => (string) Str::uuid(),
            'support' => false,
        ]);

        $seed = $this->seedInPersonCourseWithSession($user, false);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/student/session-confirm', [
            'session_id' => $seed['session']->id,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.course_session_id', $seed['session']->id);

        $this->assertNotNull(
            UserAdmission::query()->where('user_id', $user->userId)->value('confirmed')
        );
    }

    public function test_session_confirm_409_when_session_full(): void
    {
        $user = User::factory()->create([
            'userId' => (string) Str::uuid(),
            'support' => false,
        ]);

        $seed = $this->seedInPersonCourseWithSession($user, false);
        $seed['session']->limit = 0;
        $seed['session']->save();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/student/session-confirm', [
            'session_id' => $seed['session']->id,
        ])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'session_full');
    }

    public function test_session_options_programme_mismatch_returns_409(): void
    {
        $user = User::factory()->create([
            'userId' => (string) Str::uuid(),
            'support' => false,
        ]);

        $this->seedInPersonCourseWithSession($user, false);

        $otherProgramme = Programme::query()->create([
            'title' => 'Other Programme',
            'mode_of_delivery' => 'In person',
            'status' => true,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/student/session-options?programme_id='.$otherProgramme->id)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'programme_mismatch');
    }
}
