<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Centre;
use App\Models\CentreSession;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\MasterSession;
use App\Models\OldAdmission;
use App\Models\Programme;
use App\Models\User;
use App\Models\UserAdmission;
use App\Services\CentreDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CentreDeletionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_centre_children_before_deleting_the_centre(): void
    {
        $branch = Branch::create([
            'title' => 'Greater Accra',
            'status' => true,
        ]);

        $programme = Programme::create([
            'title' => 'Digital Skills',
            'duration' => 20,
            'duration_in_days' => 10,
            'time_allocation' => 2,
        ]);

        $centre = Centre::create([
            'title' => 'Accra Centre',
            'branch_id' => $branch->id,
            'status' => true,
        ]);

        $otherCentre = Centre::create([
            'title' => 'Tema Centre',
            'branch_id' => $branch->id,
            'status' => true,
        ]);

        $course = Course::create([
            'centre_id' => $centre->id,
            'programme_id' => $programme->id,
            'course_name' => 'Placeholder',
            'status' => true,
        ]);

        $otherCourse = Course::create([
            'centre_id' => $otherCentre->id,
            'programme_id' => $programme->id,
            'course_name' => 'Placeholder Two',
            'status' => true,
        ]);

        $masterSession = MasterSession::create([
            'master_name' => 'Morning',
            'session_type' => 'course',
            'time' => '09:00-11:00',
            'course_type' => 'short',
            'status' => true,
        ]);

        $courseSession = CourseSession::create([
            'master_session_id' => $masterSession->id,
            'course_id' => $course->id,
            'centre_id' => $centre->id,
            'session_type' => CourseSession::TYPE_COURSE,
            'limit' => 20,
            'course_time' => '09:00-11:00',
            'session' => 'Morning',
            'status' => true,
        ]);

        $otherCourseSession = CourseSession::create([
            'master_session_id' => $masterSession->id,
            'course_id' => $otherCourse->id,
            'centre_id' => $otherCentre->id,
            'session_type' => CourseSession::TYPE_COURSE,
            'limit' => 20,
            'course_time' => '09:00-11:00',
            'session' => 'Morning',
            'status' => true,
        ]);

        $centreSession = CentreSession::create([
            'master_session_id' => $masterSession->id,
            'centre_id' => $centre->id,
            'limit' => 20,
            'course_time' => '13:00-15:00',
            'session' => 'Afternoon',
            'status' => true,
        ]);

        $user = User::create([
            'userId' => 'CENTRE-USER-1',
            'name' => 'Centre User',
            'email' => 'centre-user@example.com',
            'registered_course' => $course->id,
        ]);

        $otherUser = User::create([
            'userId' => 'CENTRE-USER-2',
            'name' => 'Other User',
            'email' => 'other-centre-user@example.com',
            'registered_course' => $otherCourse->id,
        ]);

        UserAdmission::create([
            'user_id' => $user->userId,
            'course_id' => $course->id,
            'session' => $courseSession->id,
            'confirmed' => now(),
            'email_sent' => now(),
        ]);

        UserAdmission::create([
            'user_id' => $otherUser->userId,
            'course_id' => $otherCourse->id,
            'session' => $otherCourseSession->id,
            'confirmed' => now(),
            'email_sent' => now(),
        ]);

        OldAdmission::create([
            'user_id' => $user->userId,
            'course_id' => $course->id,
            'session' => $courseSession->id,
            'confirmed' => now(),
            'email_sent' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->userId,
            'course_id' => $course->id,
            'date' => now()->toDateString(),
            'status' => 'present',
        ]);

        app(CentreDeletionService::class)->delete($centre->fresh());

        $this->assertDatabaseMissing('centres', ['id' => $centre->id]);
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
        $this->assertDatabaseMissing('course_sessions', ['id' => $courseSession->id]);
        $this->assertDatabaseMissing('course_sessions', ['id' => $centreSession->id]);
        $this->assertDatabaseMissing('user_admission', ['user_id' => $user->userId]);
        $this->assertDatabaseMissing('old_admissions', ['user_id' => $user->userId, 'course_id' => $course->id]);
        $this->assertDatabaseMissing('attendances', ['user_id' => $user->userId, 'course_id' => $course->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'registered_course' => null]);

        $this->assertDatabaseHas('centres', ['id' => $otherCentre->id]);
        $this->assertDatabaseHas('courses', ['id' => $otherCourse->id]);
        $this->assertDatabaseHas('course_sessions', ['id' => $otherCourseSession->id]);
        $this->assertDatabaseHas('user_admission', ['user_id' => $otherUser->userId, 'course_id' => $otherCourse->id]);
    }
}
