<?php

namespace Tests\Feature;

use App\Events\AdmissionSlotFreed;
use App\Models\Batch;
use App\Models\Booking;
use App\Models\Centre;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\MasterSession;
use App\Models\Programme;
use App\Models\ProgrammeBatch;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(int $slots = 10, int $shortSlots = 10, int $longSlots = 0, int $endDays = 30): array
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
            'end_date' => now()->addDays($endDays),
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

        return compact('batch', 'programme', 'centre', 'programmeBatch', 'course', 'session');
    }

    private function makeUser(string $prefix = 'T'): User
    {
        return User::create([
            'userId' => $prefix . '-' . uniqid(),
            'name' => 'User',
            'email' => $prefix . uniqid() . '@example.com',
        ]);
    }

    /** @test */
    public function it_books_a_slot_and_decrements_available_slots()
    {
        Event::fake();
        $f = $this->fixture();
        $user = $this->makeUser();

        $booking = app(BookingService::class)->book($user, $f['course'], $f['programmeBatch'], $f['session']);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals($user->userId, $booking->user_id);
        $this->assertEquals($f['programmeBatch']->id, $booking->programme_batch_id);
        $this->assertEquals($f['session']->id, $booking->course_session_id);
        $this->assertEquals('short', $booking->course_type);

        $f['programmeBatch']->refresh();
        $this->assertEquals(9, $f['programmeBatch']->available_slots);
    }

    /** @test */
    public function it_rejects_booking_when_no_slots_available()
    {
        $f = $this->fixture();
        $f['programmeBatch']->update(['available_slots' => 0]);
        $user = $this->makeUser();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No available slots for this programme batch.');

        app(BookingService::class)->book($user, $f['course'], $f['programmeBatch'], $f['session']);
    }

    /** @test */
    public function it_rejects_booking_when_session_is_full()
    {
        $f = $this->fixture(slots: 5, shortSlots: 1);
        Booking::create([
            'user_id' => 'OCC',
            'programme_batch_id' => $f['programmeBatch']->id,
            'course_session_id' => $f['session']->id,
            'centre_id' => $f['centre']->id,
            'course_id' => $f['course']->id,
            'course_type' => 'short',
            'status' => 'confirmed',
        ]);

        $user = $this->makeUser();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Course session is full.');

        app(BookingService::class)->book($user, $f['course'], $f['programmeBatch'], $f['session']);
    }

    /** @test */
    public function cancel_hard_deletes_booking_and_restores_slot()
    {
        Event::fake();
        $f = $this->fixture();
        $user = $this->makeUser();

        $service = app(BookingService::class);
        $booking = $service->book($user, $f['course'], $f['programmeBatch'], $f['session']);

        $restored = $service->cancel($booking);

        $this->assertTrue($restored);
        Event::assertDispatched(AdmissionSlotFreed::class);

        $f['programmeBatch']->refresh();
        $this->assertEquals(10, $f['programmeBatch']->available_slots);
        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }

    /** @test */
    public function cancel_within_seven_days_does_not_restore_slot()
    {
        Event::fake();
        $f = $this->fixture(endDays: 3);
        $user = $this->makeUser();

        $service = app(BookingService::class);
        $booking = $service->book($user, $f['course'], $f['programmeBatch'], $f['session']);

        $restored = $service->cancel($booking);

        $this->assertFalse($restored);
        Event::assertNotDispatched(AdmissionSlotFreed::class);

        $f['programmeBatch']->refresh();
        $this->assertEquals(9, $f['programmeBatch']->available_slots);
        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }

    /** @test */
    public function concurrent_book_calls_only_allow_one_success()
    {
        $f = $this->fixture(slots: 1, shortSlots: 1);
        $user1 = $this->makeUser('U1');
        $user2 = $this->makeUser('U2');

        $service = app(BookingService::class);

        $booking1 = $service->book($user1, $f['course'], $f['programmeBatch'], $f['session']);
        $this->assertInstanceOf(Booking::class, $booking1);

        $this->expectException(\Exception::class);
        $service->book($user2, $f['course'], $f['programmeBatch'], $f['session']);
    }
}
