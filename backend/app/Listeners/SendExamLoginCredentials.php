<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Jobs\SendExamLoginCredentialsJob;
use Illuminate\Support\Facades\Mail;
use App\Mail\ExamLoginCredentials;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendExamLoginCredentials implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\UserRegistered  $event
     * @return void
     */
    public function handle(UserRegistered $event)
    {
        // Mail::to($event->user->email)->send(new ExamLoginCredentials($event->user));
        SendExamLoginCredentialsJob::dispatch($event->std, $event->plainPassword);
    }
}
