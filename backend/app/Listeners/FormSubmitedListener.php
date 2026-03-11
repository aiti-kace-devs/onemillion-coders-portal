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
        $mobileNumberField = $event->submissionData['phone_number_field'] ?? null;

        $firstName = $this->getField($event->submissionData, 'first_name', 'first-name');
        $middleName = $this->getField($event->submissionData, 'middle_name', 'middle-name');
        $lastName = $this->getField($event->submissionData, 'last_name', 'last-name');

        $fullName = trim(($firstName ?? '') . ' ' . ($middleName ?? '') . ' ' . ($lastName ?? ''));
        $fullName = preg_replace('/\s+/', ' ', $fullName); // remove extra spaces

        $student = [];
        $student['name'] = $fullName; 
        $student['first_name'] = $firstName;
        $student['middle_name'] = $middleName;
        $student['last_name'] = $lastName;
        $student['email'] = $this->getField($event->submissionData, 'email');
        $student['mobile_no'] = $mobileNumberField
            ? $this->getField(
                $event->submissionData,
                $mobileNumberField,
                str_replace('_', '-', $mobileNumberField)
            )
            : null;
        $student['userId'] = Str::uuid()->toString();
        $registeredCourse = $this->getField($event->submissionData, 'course', 'course_id');
        $student['registered_course'] = !empty($registeredCourse) ? $registeredCourse : null;
        $student['age'] = $this->getField($event->submissionData, 'age');
        $student['gender'] = $this->getField($event->submissionData, 'gender');
        $student['ghcard'] = $this->getField($event->submissionData, 'ghana_card_number', 'ghana-card-number', 'ghcard');
        $student['has_disability'] = $this->extractDisabilityFlag($event->submissionData);
        $student['exam_name'] = 'random';
        $student['form_response_id'] = $event->formResponseId;

        $extraData = [];
        $highestEducation = $this->getField($event->submissionData, 'highest_level_of_education', 'highest-level-of-education');
        if (!empty($highestEducation)) {
            $extraData['highest_level_of_education'] = $highestEducation;
        }

        $certificate = $this->getField($event->submissionData, 'certificate');
        if (is_array($certificate) && !empty($certificate['url'])) {
            $extraData['certificate'] = $certificate['url'];
        } elseif (!empty($certificate)) {
            $extraData['certificate'] = $certificate;
        }

        if (!empty($extraData)) {
            $student['data'] = $extraData;
        }

        AddNewStudentsJob::dispatch([$student]);
    }

    private function getField(array $payload, string ...$keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                return $payload[$key];
            }
        }

        return null;
    }

    private function extractDisabilityFlag(array $payload): bool
    {
        foreach ($payload as $key => $value) {
            if (stripos((string) $key, 'disability') === false) {
                continue;
            }

            if (is_bool($value)) {
                return $value;
            }

            if (is_numeric($value)) {
                return (int) $value === 1;
            }

            $normalized = strtolower(trim((string) $value));
            if (in_array($normalized, ['1', 'true', 'yes', 'y'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'no', 'n'], true)) {
                return false;
            }
        }

        return false;
    }

}
