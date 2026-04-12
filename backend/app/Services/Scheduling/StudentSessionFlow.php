<?php

namespace App\Services\Scheduling;

use App\Models\Course;
use App\Models\User;

/**
 * "Centre support" flow: student intends to use a physical centre (users.support = true).
 * That includes online programmes where the learner will attend at a preferred centre, as well as in-person programmes.
 *
 * "Simple" flow: users.support = false — e.g. online learner studying fully remote (no centre booking,
 * no geographic alternatives; capacity still uses programme quota + course_sessions.limit via slotLeft).
 */
final class StudentSessionFlow
{
    public const FLOW_SIMPLE = 'simple';

    public const FLOW_CENTRE_SUPPORT = 'centre_support';

    public static function requiresCentreSupportFlow(User $user, Course $course): bool
    {
        return (bool) $user->support;
    }

    /**
     * Online programme and student does not need centre attendance (fully remote).
     */
    public static function isFullyRemoteOnline(User $user, Course $course): bool
    {
        return $course->isOnlineProgramme() && ! $user->support;
    }

    public static function flowLabel(User $user, Course $course): string
    {
        return self::requiresCentreSupportFlow($user, $course)
            ? self::FLOW_CENTRE_SUPPORT
            : self::FLOW_SIMPLE;
    }
}
