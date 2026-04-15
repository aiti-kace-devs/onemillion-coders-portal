<?php

namespace App\Helpers;

use Carbon\Carbon;

/**
 * Utility methods for school-day (Mon–Fri) calculations.
 */
class SchoolDayCalculator
{
    /**
     * Count school days (Mon–Fri) between two dates inclusive.
     */
    public static function count(Carbon $start, Carbon $end): int
    {
        if ($start->gt($end)) {
            return 0;
        }

        $count = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            $dayOfWeek = $current->dayOfWeek;
            if ($dayOfWeek >= Carbon::MONDAY && $dayOfWeek <= Carbon::FRIDAY) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    /**
     * Add N school days (Mon–Fri) to a date, skipping weekends.
     */
    public static function add(Carbon $from, int $days): Carbon
    {
        $result = $from->copy();
        $added = 0;

        while ($added < $days) {
            $result->addDay();
            $dayOfWeek = $result->dayOfWeek;
            if ($dayOfWeek >= Carbon::MONDAY && $dayOfWeek <= Carbon::FRIDAY) {
                $added++;
            }
        }

        return $result;
    }
}
