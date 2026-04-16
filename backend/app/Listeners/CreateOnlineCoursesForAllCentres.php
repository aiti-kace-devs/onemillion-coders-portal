<?php

namespace App\Listeners;

use App\Events\OnlineProgrammeSaved;
use App\Models\Centre;
use App\Models\Course;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CreateOnlineCoursesForAllCentres
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OnlineProgrammeSaved $event): void
    {
        $programme = $event->programme;

        // Get all active centres
        $centres = Centre::where('status', true)->get();

        // Get existing courses for this programme
        $existingCourses = Course::where('programme_id', $programme->id)
            ->whereIn('centre_id', $centres->pluck('id'))
            ->pluck('centre_id')
            ->toArray();

        // Find centres that don't have courses for this programme
        $centresWithoutCourses = $centres->filter(function ($centre) use ($existingCourses) {
            return !in_array($centre->id, $existingCourses);
        });

        // Log::info(' programme IDs: ' . $centresWithoutCourses->pluck('id')->implode(', '));

        // Create courses for centres that don't have them
        foreach ($centresWithoutCourses as $centre) {
            $courseName = $programme->title . ' - (' . $centre->title . ')';

            Course::create([
                'centre_id' => $centre->id,
                'programme_id' => $programme->id,
                'course_name' => $courseName,
                'duration' => $programme->duration,
                'status' => true,
                'start_date' => $programme->start_date,
                'end_date' => $programme->end_date,
            ]);
        }
    }
}
