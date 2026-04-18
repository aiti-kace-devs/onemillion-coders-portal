<?php

namespace App\Providers;

use App\Events\FormSubmittedEvent;
use App\Events\UserRegistered;
use App\Events\CourseChanged;
use App\Events\AdmissionSlotFreed;
use App\Events\AdmissionDeleted;
use App\Events\ProgrammeBatchCreated;
use App\Listeners\EmailSentListener;
use App\Listeners\FormSubmitedListener;
use App\Listeners\SendExamLoginCredentials;
use App\Listeners\CourseChangedListener;
use App\Listeners\NotifyWaitlistedUsers;
use App\Listeners\NotifyWaitlistedUsersOnAdmissionDeleted;
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
        CourseChanged::class => [
            CourseChangedListener::class,
        ],
        \Illuminate\Queue\Events\JobProcessed::class => [
            EmailSentListener::class
        ],
        AdmissionSlotFreed::class => [
            NotifyWaitlistedUsers::class . '@onSlotFreed',
        ],
        AdmissionDeleted::class => [
            NotifyWaitlistedUsersOnAdmissionDeleted::class,
        ],
        ProgrammeBatchCreated::class => [
            NotifyWaitlistedUsers::class . '@onBatchCreated',
        ],
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
        \App\Models\Batch::observe(\App\Observers\BatchObserver::class);
        \App\Models\Centre::observe(\App\Observers\CentreObserver::class);
        \App\Models\Booking::observe(\App\Observers\BookingObserver::class);
        \App\Models\ProgrammeBatch::observe(\App\Observers\ProgrammeBatchObserver::class);
        \App\Models\UserAdmission::observe(\App\Observers\UserAdmissionObserver::class);
    }
}
