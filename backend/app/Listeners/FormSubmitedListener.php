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


    public function handle(FormSubmittedEvent $event): void
    {
        $mobileNumberField = $event->submissionData['phone_number_field'];

        $fullName = trim(
            ($event->submissionData['first-name'] ?? '') . ' ' .
            ($event->submissionData['middle-name'] ?? '') . ' ' .
            ($event->submissionData['last-name'] ?? '')
        );
        $fullName = preg_replace('/\s+/', ' ', $fullName); // remove extra spaces

        $student = [];
        $student['name'] = $fullName; 
        $student['first_name'] = $event->submissionData['first-name'] ?? null;
        $student['middle_name'] = $event->submissionData['middle-name'] ?? null;
        $student['last_name'] = $event->submissionData['last-name'] ?? null;
        $student['email'] = $event->submissionData['email'] ?? null;
        $student['mobile_no'] = $event->submissionData[$mobileNumberField] ?? null;
        $student['userId'] = Str::uuid()->toString();
        $student['registered_course'] = $event->submissionData['course'] ?? null;
        $student['age'] = $event->submissionData['age'] ?? null;
        $student['gender'] = $event->submissionData['gender'] ?? null;
        $student['ghcard'] = $event->submissionData['ghana-card-number'] ?? null;
        $student['exam_name'] = 'random';
        $student['form_response_id'] = $event->formResponseId;

        AddNewStudentsJob::dispatch([$student]);
    }

}
