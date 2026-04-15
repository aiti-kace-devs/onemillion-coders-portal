<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Booking;
use App\Models\Centre;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\MasterSession;
use App\Models\Programme;
use App\Models\ProgrammeBatch;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeFixture(int $slots = 10, int $shortSlots = 10, int $longSlots = 0): array
    {
        $batch = Batch::create([
            'title' => 'Admission Batch',
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(60),
            'status' => true,
        ]);

        $programme = Programme::create([
            'title' => 'Programme',
            'duration' => 20,
            'duration_in_days' => 10,
            'time_allocation' => 2,
        ]);

        $centre = Centre::create([
            'title' => 'Centre',
            'branch_id' => 1,
            'seat_count' => $slots,
            'short_slots_per_day' => $shortSlots,
            'long_slots_per_day' => $longSlots,
        ]);

        $programmeBatch = ProgrammeBatch::create([
            'admission_batch_id' => $batch->id,
            'programme_id' => $programme->id,
            'centre_id' => $centre->id,
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(30),
            'max_enrolments' => $slots,
            'available_slots' => $slots,
            'status' => true,
        ]);

        $course = Course::create([
            'programme_id' => $programme->id,
            'centre_id' => $centre->id,
            'batch_id' => $batch->id,
            'course_name' => 'Course',
        ]);

        $masterSession = MasterSession::create([
            'master_name' => 'Morning',
            'session_type' => 'course',
            'time' => '09:00-11:00',
            'course_type' => 'short',
            'status' => true,
        ]);

        $session = CourseSession::create([
            'name' => 'Morning Session',
            'master_session_id' => $masterSession->id,
            'course_id' => $course->id,
            'centre_id' => $centre->id,
            'session_type' => 'course',
            'status' => true,
        ]);

        $user = User::create([
            'userId' => 'API-' . uniqid(),
            'name' => 'Test User',
            'email' => 'api' . uniqid() . '@example.com',
        ]);

        $token = app(JwtService::class)->generate($user->id);

        return compact('batch', 'programme', 'centre', 'programmeBatch', 'course', 'session', 'user', 'token');
    }

    private function bookingPayload(array $f): array
    {
        return [
            'programme_batch_id' => $f['programmeBatch']->id,
            'course_id' => $f['course']->id,
            'course_session_id' => $f['session']->id,
        ];
    }

    /** @test */
    public function post_bookings_reserves_a_slot()
    {
        $f = $this->makeFixture(5);

        $response = $this->withHeader('Authorization', 'Bearer ' . $f['token'])
            ->postJson('/api/bookings', $this->bookingPayload($f));

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success');

        $f['programmeBatch']->refresh();
        $this->assertEquals(4, $f['programmeBatch']->available_slots);
        $this->assertDatabaseHas('bookings', [
            'user_id' => $f['user']->userId,
            'programme_batch_id' => $f['programmeBatch']->id,
            'course_session_id' => $f['session']->id,
            'status' => 'confirmed',
        ]);
        $this->assertDatabaseHas('user_admission', [
            'user_id' => $f['user']->userId,
            'programme_batch_id' => $f['programmeBatch']->id,
        ]);
    }

    /** @test */
    public function post_bookings_rejects_course_from_different_programme()
    {
        $f = $this->makeFixture(5);
        $otherProgramme = Programme::create([
            'title' => 'Other',
            'duration' => 20,
            'duration_in_days' => 10,
            'time_allocation' => 2,
        ]);
        $wrongCourse = Course::create([
            'programme_id' => $otherProgramme->id,
            'centre_id' => $f['centre']->id,
            'batch_id' => $f['batch']->id,
            'course_name' => 'Wrong',
        ]);

        $payload = $this->bookingPayload($f);
        $payload['course_id'] = $wrongCourse->id;

        $response = $this->withHeader('Authorization', 'Bearer ' . $f['token'])
            ->postJson('/api/bookings', $payload);

        $response->assertStatus(422);
        $f['programmeBatch']->refresh();
        $this->assertEquals(5, $f['programmeBatch']->available_slots);
    }

    /** @test */
    public function post_bookings_returns_409_when_batch_full()
    {
        $f = $this->makeFixture(1);
        $f['programmeBatch']->update(['available_slots' => 0]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $f['token'])
            ->postJson('/api/bookings', $this->bookingPayload($f));

        $response->assertStatus(409)
            ->assertJsonStructure(['status', 'message', 'recommendations']);
    }

    /** @test */
    public function post_bookings_returns_409_when_session_capacity_exhausted()
    {
        // Batch has room, but session-level capacity (short_slots_per_day) is 1 and already used.
        $f = $this->makeFixture(5, shortSlots: 1, longSlots: 0);

        $otherUser = User::create([
            'userId' => 'OCCUPANT',
            'name' => 'Occupant',
            'email' => 'occupant@example.com',
        ]);

        Booking::create([
            'user_id' => $otherUser->userId,
            'programme_batch_id' => $f['programmeBatch']->id,
            'course_session_id' => $f['session']->id,
            'centre_id' => $f['centre']->id,
            'course_id' => $f['course']->id,
            'course_type' => 'short',
            'status' => 'confirmed',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $f['token'])
            ->postJson('/api/bookings', $this->bookingPayload($f));

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Course session is full.');
    }

    /** @test */
    public function delete_bookings_restores_slot_when_eligible()
    {
        $f = $this->makeFixture(5);

        $this->withHeader('Authorization', 'Bearer ' . $f['token'])
            ->postJson('/api/bookings', $this->bookingPayload($f))
            ->assertStatus(201);

        $booking = Booking::where('user_id', $f['user']->userId)->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $f['token'])
            ->deleteJson('/api/bookings/' . $booking->id);

        $response->assertStatus(200)
            ->assertJsonPath('slot_restored', true);

        $f['programmeBatch']->refresh();
        $this->assertEquals(5, $f['programmeBatch']->available_slots);
        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
        $this->assertDatabaseHas('user_admission', [
            'user_id' => $f['user']->userId,
            'programme_batch_id' => null,
        ]);
    }

    /** @test */
    public function delete_bookings_forbids_other_users_bookings()
    {
        $f = $this->makeFixture(5);
        $other = User::create([
            'userId' => 'OTHER-' . uniqid(),
            'name' => 'Other',
            'email' => 'other' . uniqid() . '@example.com',
        ]);
        $foreignBooking = Booking::create([
            'user_id' => $other->userId,
            'programme_batch_id' => $f['programmeBatch']->id,
            'course_session_id' => $f['session']->id,
            'centre_id' => $f['centre']->id,
            'course_id' => $f['course']->id,
            'course_type' => 'short',
            'status' => 'confirmed',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $f['token'])
            ->deleteJson('/api/bookings/' . $foreignBooking->id);

        $response->assertStatus(403);
    }

    /** @test */
    public function get_bookings_mine_lists_only_current_users_bookings()
    {
        $f = $this->makeFixture(5);
        $this->withHeader('Authorization', 'Bearer ' . $f['token'])
            ->postJson('/api/bookings', $this->bookingPayload($f))
            ->assertStatus(201);

        $response = $this->withHeader('Authorization', 'Bearer ' . $f['token'])
            ->getJson('/api/bookings/mine');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function bookings_require_auth_token()
    {
        $this->postJson('/api/bookings', [])->assertStatus(401);
    }
}
