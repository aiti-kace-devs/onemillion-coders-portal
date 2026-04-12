<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\Programme;
use App\Models\User;
use App\Services\Scheduling\StudentSessionFlow;
use PHPUnit\Framework\TestCase;

class StudentSessionFlowTest extends TestCase
{
    private function courseWithMode(string $mode): Course
    {
        $programme = new Programme(['mode_of_delivery' => $mode]);
        $course = new Course(['centre_id' => 1, 'batch_id' => 1]);
        $course->setRelation('programme', $programme);

        return $course;
    }

    public function test_centre_support_flow_when_student_needs_support_regardless_of_online(): void
    {
        $user = new User(['support' => true]);

        $this->assertTrue(StudentSessionFlow::requiresCentreSupportFlow($user, $this->courseWithMode('online')));
        $this->assertSame(StudentSessionFlow::FLOW_CENTRE_SUPPORT, StudentSessionFlow::flowLabel($user, $this->courseWithMode('online')));

        $this->assertTrue(StudentSessionFlow::requiresCentreSupportFlow($user, $this->courseWithMode('In person')));
        $this->assertSame(StudentSessionFlow::FLOW_CENTRE_SUPPORT, StudentSessionFlow::flowLabel($user, $this->courseWithMode('In person')));
    }

    public function test_simple_flow_when_student_does_not_need_support_regardless_of_online(): void
    {
        $user = new User(['support' => false]);

        $this->assertFalse(StudentSessionFlow::requiresCentreSupportFlow($user, $this->courseWithMode('online')));
        $this->assertSame(StudentSessionFlow::FLOW_SIMPLE, StudentSessionFlow::flowLabel($user, $this->courseWithMode('online')));

        $this->assertFalse(StudentSessionFlow::requiresCentreSupportFlow($user, $this->courseWithMode('In person')));
        $this->assertSame(StudentSessionFlow::FLOW_SIMPLE, StudentSessionFlow::flowLabel($user, $this->courseWithMode('In person')));
    }

    public function test_fully_remote_only_for_online_without_support(): void
    {
        $online = $this->courseWithMode('online');
        $inPerson = $this->courseWithMode('In person');

        $this->assertTrue(StudentSessionFlow::isFullyRemoteOnline(new User(['support' => false]), $online));
        $this->assertFalse(StudentSessionFlow::isFullyRemoteOnline(new User(['support' => true]), $online));
        $this->assertFalse(StudentSessionFlow::isFullyRemoteOnline(new User(['support' => false]), $inPerson));
    }

    public function test_mode_of_delivery_is_case_insensitive_for_online(): void
    {
        $userRemote = new User(['support' => false]);
        foreach (['Online', 'ONLINE', ' online '] as $mode) {
            $course = $this->courseWithMode($mode);
            $this->assertTrue(
                StudentSessionFlow::isFullyRemoteOnline($userRemote, $course),
                "Expected fully remote for mode: {$mode}"
            );
        }
    }
}
