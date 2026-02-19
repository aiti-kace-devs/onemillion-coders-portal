<?php

namespace App\Listeners;

use App\Events\CourseChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CourseChangedListener
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
    public function handle(CourseChanged $event): void
    {
        //TODO: Create new exam record for the student based on the new course selection

    }
}
