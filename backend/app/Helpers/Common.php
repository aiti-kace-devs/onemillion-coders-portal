<?php

namespace App\Helpers;

use Carbon\Carbon;


trait Common
{

    public function countWeekdays($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $weekdays = 0;
        $currentDay = $start->copy();

        while ($currentDay->lte($end)) {
            if ($currentDay->isWeekday()) {
                $weekdays++;
            }
            $currentDay->addDay();
        }

        return $weekdays;
    }

    public function getWeekdays($startDate, $endDate, $format = null)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $dates = [];

        $currentDay = $start->copy();

        while ($currentDay->lte($end)) {
            if ($currentDay->isWeekday()) {
                if ($format) {
                    $dates[] = $currentDay->format($format);
                } else {
                    $dates[] = $currentDay->toDateString();
                }
            }
            $currentDay->addDay();
        }

        return $dates;
    }
}
