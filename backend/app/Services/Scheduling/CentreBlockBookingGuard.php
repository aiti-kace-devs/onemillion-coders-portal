<?php

namespace App\Services\Scheduling;

use App\Models\CentreTimeBlock;
use App\Models\Course;
use App\Models\StudentCentreBooking;
use App\Models\User;

final class CentreBlockBookingGuard
{
    public static function isEnabled(): bool
    {
        return (bool) config('scheduling.require_centre_block_for_confirm', false);
    }

    public static function appliesTo(User $user, Course $course): bool
    {
        return self::isEnabled() && StudentSessionFlow::requiresCentreSupportFlow($user, $course);
    }

    /**
     * Active blocks that apply to this course's centre / cohort (broad match for v1).
     */
    public static function applicableBlockIds(Course $course): array
    {
        if (! $course->centre_id) {
            return [];
        }

        return CentreTimeBlock::query()
            ->where('is_active', true)
            ->where('centre_id', $course->centre_id)
            ->where(function ($q) use ($course) {
                $q->whereNull('batch_id')->orWhere('batch_id', $course->batch_id);
            })
            ->where(function ($q) use ($course) {
                $q->whereNull('programme_id')->orWhere('programme_id', $course->programme_id);
            })
            ->where(function ($q) use ($course) {
                $q->whereNull('course_id')->orWhere('course_id', $course->id);
            })
            ->pluck('id')
            ->all();
    }

    public static function hasConfirmedBooking(User $user, Course $course): bool
    {
        $ids = self::applicableBlockIds($course);
        if ($ids === []) {
            return true;
        }

        return StudentCentreBooking::query()
            ->where('user_id', $user->userId)
            ->whereIn('centre_time_block_id', $ids)
            ->where('status', StudentCentreBooking::STATUS_CONFIRMED)
            ->exists();
    }

    public static function passes(User $user, Course $course): bool
    {
        if (! self::appliesTo($user, $course)) {
            return true;
        }

        $ids = self::applicableBlockIds($course);
        if ($ids === []) {
            return true;
        }

        return self::hasConfirmedBooking($user, $course);
    }
}
