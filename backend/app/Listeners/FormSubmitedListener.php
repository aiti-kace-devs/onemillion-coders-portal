<?php

namespace App\Listeners;

use App\Events\FormSubmittedEvent;
use App\Jobs\AddNewStudentsJob;
use App\Jobs\SendSMSAfterRegistrationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FormSubmitedListener implements ShouldQueue
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
    public function handle(FormSubmittedEvent $event): void
    {

        $mobileNumberField = $event->submissionData['phone_number_field'];

        $student = [];

        $student['name'] = $event->submissionData['name'];
        $student['email'] = $event->submissionData['email'];
        $student['mobile_no'] = $event->submissionData[$mobileNumberField];
        $student['userId'] = Str::uuid()->toString();
        $student['registered_course'] = $event->submissionData['course_id'];
        $student['age'] = $event->submissionData['age'];
        $student['gender'] = $event->submissionData['gender'];
        $student['exam_name'] = 'random';
        $student['form_response_id'] = $event->formResponseId;

        AddNewStudentsJob::dispatch([$student]);
    }
}
