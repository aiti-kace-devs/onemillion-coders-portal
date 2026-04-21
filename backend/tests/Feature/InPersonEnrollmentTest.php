<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Centre;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\MasterSession;
use App\Models\Programme;
use App\Models\ProgrammeBatch;
use App\Models\User;
use App\Services\GhanaCardService;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InPersonEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(GhanaCardService::class, function ($mock) {
            $mock->allows('isVerified')->andReturn(true);
            $mock->allows('buildStatus')->andReturn([
                'attempts' => 0,
                'blocked' => false,
                'block' => null,
            ]);
        });
    }

    /**
     * @return array{branch: Branch, admission: Batch, programme: Programme, centre: Centre, pb: ProgrammeBatch, course: Course, session: CourseSession, user: User, token: string}
     */
    private function makeInPersonFixture(int $limit = 5): array
    {
        $branch = Branch::create(['title' => 'Greater Accra', 'status' => true]);

        $admission = Batch::create([
            'title' => 'Open Intake',
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonths(3),
            'status' => true,
            'completed' => false,
        ]);

        $programme = Programme::create([
            'title' => 'In-person programme',
            'duration' => '1 week',
            'duration_in_days' => 5,
            'time_allocation' => Programme::TIME_ALLOCATION_SHORT,
            'mode_of_delivery' => 'In Person',
            'status' => true,
        ]);

        $centre = Centre::create([
            'title' => 'GI-KACE Training Centre',
            'branch_id' => $branch->id,
            'seat_count' => 50,
            'short_slots_per_day' => 10,
            'long_slots_per_day' => 0,
            'status' => true,
        ]);

        $pb = ProgrammeBatch::create([
            'admission_batch_id' => $admission->id,
            'programme_id' => $programme->id,
            'start_date' => now()->addWeek(),
            'end_date' => now()->addWeeks(3),
            'status' => true,
        ]);

        $course = Course::create([
            'programme_id' => $programme->id,
            'centre_id' => $centre->id,
            'batch_id' => $admission->id,
            'course_name' => 'DARE Certificate',
            'status' => true,
        ]);

        $session = CourseSession::create([
            'name' => 'Morning Session',
            'course_id' => $course->id,
            'centre_id' => $centre->id,
            'session_type' => CourseSession::TYPE_CENTRE,
            'course_time' => '8AM - 9:45AM',
            'session' => 'Morning',
            'limit' => $limit,
            'status' => true,
        ]);

        $user = User::create([
            'userId' => 'test-ip-'.uniqid(),
            'name' => 'Student',
            'email' => 'ip-'.uniqid().'@test.local',
            'password' => Hash::make('password'),
        ]);

        $token = app(JwtService::class)->generate($user->id);

        return compact('branch', 'admission', 'programme', 'centre', 'pb', 'course', 'session', 'user', 'token');
    }

    /** @test */
    public function in_person_availability_lists_batches_and_sessions(): void
    {
        $f = $this->makeInPersonFixture();

        $res = $this->withHeader('Authorization', 'Bearer '.$f['token'])
            ->getJson('/api/availability/in-person/batches?course_id='.$f['course']->id);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['session_id' => $f['session']->id])
            ->assertJsonPath('batches.0.sessions.0.show_seat_count', true);
    }

    /** @test */
    public function in_person_availability_falls_back_to_master_sessions_when_centre_rows_are_missing(): void
    {
        $f = $this->makeInPersonFixture();
        $f['session']->delete();

        $master = MasterSession::create([
            'master_name' => 'Short Morning',
            'session_type' => 'Morning',
            'time' => '8AM - 9:45AM',
            'course_type' => Programme::COURSE_TYPE_SHORT,
            'status' => true,
        ]);

        $res = $this->withHeader('Authorization', 'Bearer '.$f['token'])
            ->getJson('/api/availability/batches?course_id='.$f['course']->id);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['session_id' => $master->id])
            ->assertJsonFragment(['master_session_id' => $master->id]);
    }

    /** @test */
    public function in_person_availability_returns_422_for_online_programme(): void
    {
        $f = $this->makeInPersonFixture();
        $f['programme']->update(['mode_of_delivery' => 'Online']);

        $res = $this->withHeader('Authorization', 'Bearer '.$f['token'])
            ->getJson('/api/availability/in-person/batches?course_id='.$f['course']->id);

        $res->assertStatus(422);
    }

    /** @test */
    public function post_in_person_enrollment_succeeds_under_limit(): void
    {
        $f = $this->makeInPersonFixture(5);

        $res = $this->withHeader('Authorization', 'Bearer '.$f['token'])
            ->postJson('/api/in-person-enrollment', [
                'programme_batch_id' => $f['pb']->id,
                'course_id' => $f['course']->id,
                'course_session_id' => $f['session']->id,
            ]);

        $res->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('redirect_url', url('/student/dashboard'));

        $this->assertDatabaseHas('bookings', [
            'user_id' => $f['user']->userId,
            'programme_batch_id' => $f['pb']->id,
            'course_session_id' => $f['session']->id,
            'course_id' => $f['course']->id,
        ]);

        $booking = Booking::where('user_id', $f['user']->userId)->first();
        $this->assertNotNull($booking);
        $this->assertNull($booking->master_session_id);
    }

    /** @test */
    public function post_in_person_booking_accepts_master_session_fallback(): void
    {
        $f = $this->makeInPersonFixture(5);
        $f['session']->delete();

        $master = MasterSession::create([
            'master_name' => 'Short Morning',
            'session_type' => 'Morning',
            'time' => '8AM - 9:45AM',
            'course_type' => Programme::COURSE_TYPE_SHORT,
            'status' => true,
        ]);

        $res = $this->withHeader('Authorization', 'Bearer '.$f['token'])
            ->postJson('/api/bookings', [
                'programme_batch_id' => $f['pb']->id,
                'course_id' => $f['course']->id,
                'session_id' => $master->id,
            ]);

        $res->assertStatus(201)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('bookings', [
            'user_id' => $f['user']->userId,
            'programme_batch_id' => $f['pb']->id,
            'course_id' => $f['course']->id,
            'course_session_id' => null,
            'master_session_id' => $master->id,
        ]);
    }

    /** @test */
    public function post_in_person_enrollment_returns_409_when_at_capacity(): void
    {
        $f = $this->makeInPersonFixture(1);
        $other = User::create([
            'userId' => 'other-'.uniqid(),
            'name' => 'Other',
            'email' => 'o'.uniqid().'@test.local',
            'password' => Hash::make('password'),
        ]);

        Booking::withoutEvents(function () use ($f, $other) {
            Booking::create([
                'user_id' => $other->userId,
                'programme_batch_id' => $f['pb']->id,
                'course_session_id' => $f['session']->id,
                'master_session_id' => null,
                'centre_id' => $f['centre']->id,
                'course_id' => $f['course']->id,
                'course_type' => 'short',
                'status' => true,
                'booked_at' => now(),
            ]);
        });

        $res = $this->withHeader('Authorization', 'Bearer '.$f['token'])
            ->postJson('/api/in-person-enrollment', [
                'programme_batch_id' => $f['pb']->id,
                'course_id' => $f['course']->id,
                'course_session_id' => $f['session']->id,
            ]);

        $res->assertStatus(409);
    }
}
