<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class EmailSentListener
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
    public function handle(object $event): void
    {
        $job = unserialize($event->job->payload()['data']['command']);

        if ($job instanceof \Illuminate\Mail\SendQueuedMailable) {
            $mailable = $job->mailable;
            if ($mailable instanceof \App\Mail\GenericEmail) {
                $mailable->success();
            }
        }
    }
}
