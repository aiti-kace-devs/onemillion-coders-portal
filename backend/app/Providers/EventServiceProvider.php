<?php

namespace App\Providers;

use App\Events\FormSubmittedEvent;
use App\Events\UserRegistered;
use App\Listeners\EmailSentListener;
use App\Listeners\FormSubmitedListener;
use App\Listeners\SendExamLoginCredentials;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        UserRegistered::class => [
            SendExamLoginCredentials::class,
        ],
        FormSubmittedEvent::class => [
            FormSubmitedListener::class,
        ],
        \Illuminate\Queue\Events\JobProcessed::class => [
            EmailSentListener::class
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        \App\Models\Programme::observe(\App\Observers\ProgrammeObserver::class);
        \App\Models\Branch::observe(\App\Observers\BranchObserver::class);
        \App\Models\CourseCategory::observe(\App\Observers\CourseCategoryObserver::class);
    }
}
