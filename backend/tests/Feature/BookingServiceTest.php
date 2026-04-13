<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Centre;
use App\Models\Course;
use App\Models\Programme;
use App\Models\ProgrammeBatch;
use App\Models\User;
use App\Models\UserAdmission;
use App\Services\BookingService;
use App\Events\AdmissionSlotFreed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_books_a_slot_successfully()
    {
        Event::fake();

        $batch = Batch::create([
            'title' => 'Test Batch',
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(30),
            'status' => true,
        ]);

        $programme = Programme::create([
            'title' => 'Test Programme',
            'duration' => 20,
            'duration_in_days' => 10,
            'time_allocation' => 2,
        ]);

        $centre = Centre::create([
            'title' => 'Test Centre',
            'branch_id' => 1,
            'seat_count' => 10,
        ]);

        $programmeBatch = ProgrammeBatch::create([
            'admission_batch_id' => $batch->id,
            'programme_id' => $programme->id,
            'centre_id' => $centre->id,
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(20),
            'max_enrolments' => 10,
            'available_slots' => 10,
            'status' => true,
        ]);

        $course = Course::create([
            'programme_id' => $programme->id,
            'centre_id' => $centre->id,
            'batch_id' => $batch->id,
            'course_name' => 'Test Course',
        ]);

        $user = User::create([
            'userId' => 'TEST-' . uniqid(),
            'name' => 'Test User',
            'email' => 'test' . uniqid() . '@example.com',
        ]);

        $bookingService = app(BookingService::class);
        $admission = $bookingService->book($user, $course, $programmeBatch);

        $this->assertInstanceOf(UserAdmission::class, $admission);
        $this->assertEquals($user->userId, $admission->user_id);
        $this->assertEquals($course->id, $admission->course_id);
        $this->assertEquals($programmeBatch->id, $admission->programme_batch_id);

        // Verify slot was decremented
        $programmeBatch->refresh();
        $this->assertEquals(9, $programmeBatch->available_slots);
    }

    /** @test */
    public function it_rejects_booking_when_no_slots_available()
    {
        $batch = Batch::create([
            'title' => 'Test Batch',
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(30),
            'status' => true,
        ]);

        $programme = Programme::create([
            'title' => 'Test Programme',
            'duration' => 20,
            'duration_in_days' => 10,
            'time_allocation' => 2,
        ]);

        $centre = Centre::create([
            'title' => 'Test Centre',
            'branch_id' => 1,
            'seat_count' => 1,
        ]);

        $programmeBatch = ProgrammeBatch::create([
            'admission_batch_id' => $batch->id,
            'programme_id' => $programme->id,
            'centre_id' => $centre->id,
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(20),
            'max_enrolments' => 1,
            'available_slots' => 0, // No available slots
            'status' => true,
        ]);

        $course = Course::create([
            'programme_id' => $programme->id,
            'centre_id' => $centre->id,
            'batch_id' => $batch->id,
            'course_name' => 'Test Course',
        ]);

        $user = User::create([
            'userId' => 'TEST-' . uniqid(),
            'name' => 'Test User',
            'email' => 'test' . uniqid() . '@example.com',
        ]);

        $bookingService = app(BookingService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No available slots for this programme batch.');

        $bookingService->book($user, $course, $programmeBatch);
    }

    /** @test */
    public function it_cancels_admission_and_restores_slot_if_eligible()
    {
        Event::fake();

        $batch = Batch::create([
            'title' => 'Test Batch',
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(30),
            'status' => true,
        ]);

        $programme = Programme::create([
            'title' => 'Test Programme',
            'duration' => 20,
            'duration_in_days' => 10,
            'time_allocation' => 2,
        ]);

        $centre = Centre::create([
            'title' => 'Test Centre',
            'branch_id' => 1,
            'seat_count' => 10,
        ]);

        $programmeBatch = ProgrammeBatch::create([
            'admission_batch_id' => $batch->id,
            'programme_id' => $programme->id,
            'centre_id' => $centre->id,
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(30), // 30 days from now, > 7 days
            'max_enrolments' => 10,
            'available_slots' => 9,
            'status' => true,
        ]);

        $course = Course::create([
            'programme_id' => $programme->id,
            'centre_id' => $centre->id,
            'batch_id' => $batch->id,
            'course_name' => 'Test Course',
        ]);

        $user = User::create([
            'userId' => 'TEST-' . uniqid(),
            'name' => 'Test User',
            'email' => 'test' . uniqid() . '@example.com',
        ]);

        $bookingService = app(BookingService::class);
        $admission = $bookingService->book($user, $course, $programmeBatch);

        // Cancel the admission
        $restored = $bookingService->cancel($admission);

        $this->assertTrue($restored);
        Event::assertDispatched(AdmissionSlotFreed::class);

        $programmeBatch->refresh();
        $this->assertEquals(10, $programmeBatch->available_slots);

        // Verify admission was deleted
        $this->assertDatabaseMissing('user_admission', ['id' => $admission->id]);
    }

    /** @test */
    public function concurrent_book_calls_only_allow_one_success()
    {
        $batch = Batch::create([
            'title' => 'Test Batch',
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(30),
            'status' => true,
        ]);

        $programme = Programme::create([
            'title' => 'Test Programme',
            'duration' => 20,
            'duration_in_days' => 10,
            'time_allocation' => 2,
        ]);

        $centre = Centre::create([
            'title' => 'Test Centre',
            'branch_id' => 1,
            'seat_count' => 1,
        ]);

        $programmeBatch = ProgrammeBatch::create([
            'admission_batch_id' => $batch->id,
            'programme_id' => $programme->id,
            'centre_id' => $centre->id,
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(20),
            'max_enrolments' => 1,
            'available_slots' => 1,
            'status' => true,
        ]);

        $course = Course::create([
            'programme_id' => $programme->id,
            'centre_id' => $centre->id,
            'batch_id' => $batch->id,
            'course_name' => 'Test Course',
        ]);

        $user1 = User::create([
            'userId' => 'TEST-U1',
            'name' => 'User One',
            'email' => 'user1@example.com',
        ]);

        $user2 = User::create([
            'userId' => 'TEST-U2',
            'name' => 'User Two',
            'email' => 'user2@example.com',
        ]);

        $bookingService = app(BookingService::class);

        // Book first user
        $admission1 = $bookingService->book($user1, $course, $programmeBatch);
        $this->assertInstanceOf(UserAdmission::class, $admission1);

        // Second booking should fail (no slots left)
        $this->expectException(\Exception::class);
        $bookingService->book($user2, $course, $programmeBatch);
    }
}
