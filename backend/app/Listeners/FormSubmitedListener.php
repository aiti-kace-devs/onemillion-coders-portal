<?php

namespace App\Listeners;

use App\Events\FormSubmittedEvent;
use App\Jobs\AddNewStudentsJob;
use App\Jobs\SendSMSAfterRegistrationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
        // $student['registered_course'] = !empty($registeredCourse) ? $registeredCourse : null;
        $student['age'] = $this->getField($event->submissionData, 'age');
        $student['gender'] = $this->getField($event->submissionData, 'gender');
        $student['ghcard'] = $this->getField($event->submissionData, 'ghana_card_number', 'ghana-card-number', 'ghcard');
        if (!empty($student['ghcard'])) {
            $student['card_type'] = 'GHCARD';
        }
        $student['pwd'] = $this->extractPwdFlag($event->submissionData);
        $student['password'] = $this->getField($event->submissionData, 'password');
        $student['exam_name'] = 'random';

        $extraData = $this->buildExtraData($event->submissionData);

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

    private function extractPwdFlag(array $payload): bool
    {
        $preferredKeys = [
            'do_you_need_any_accessibility_support_pwd',
            'do-you-need-any-accessibility-support-pwd',
            'pwd',
            'has_disability',
        ];

        foreach ($preferredKeys as $key) {
            if (array_key_exists($key, $payload)) {
                return $this->normalizeBoolean($payload[$key]);
            }
        }

        foreach ($payload as $key => $value) {
            if (
                stripos((string) $key, 'disability') === false &&
                stripos((string) $key, 'accessibility') === false
            ) {
                continue;
            }

            return $this->normalizeBoolean($value);
        }

        return false;
    }

    private function normalizeBoolean($value): bool
    {
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

        return false;
    }

    private function buildExtraData(array $payload): array
    {
        $userColumns = array_flip(Schema::getColumnListing('users'));
        $extraData = [];

        $aliasMap = [
            'first_name' => 'first_name',
            'middle_name' => 'middle_name',
            'last_name' => 'last_name',
            'email' => 'email',
            'phone' => 'mobile_no',
            'phone_number' => 'mobile_no',
            'mobile' => 'mobile_no',
            'mobile_no' => 'mobile_no',
            'age' => 'age',
            'gender' => 'gender',
            'ghana_card_number' => 'ghcard',
            'ghana-card-number' => 'ghcard',
            'ghcard' => 'ghcard',
            // 'course' => 'registered_course',
            // 'course_id' => 'registered_course',
            'do_you_need_any_accessibility_support_pwd' => 'pwd',
            'do-you-need-any-accessibility-support-pwd' => 'pwd',
            'has_disability' => 'pwd',
            'pwd' => 'pwd',
            'password' => 'password',
            'do_you_require_any_special_support_for_your_training' => 'do_you_require_any_special_support_for_your_training',
        ];

        foreach ($payload as $key => $value) {
            if ($key === 'phone_number_field') {
                continue;
            }

            $normalizedKey = strtolower((string) $key);
            $normalizedKey = str_replace('-', '_', $normalizedKey);

            $alias = $aliasMap[$normalizedKey] ?? $normalizedKey;
            if (
                stripos($normalizedKey, 'disability') !== false ||
                stripos($normalizedKey, 'accessibility') !== false
            ) {
                $alias = 'pwd';
            }

            if (isset($userColumns[$alias])) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            if (is_array($value)) {
                $extraData[$normalizedKey] = $value['url'] ?? $value;
            } else {
                $extraData[$normalizedKey] = $value;
            }
        }

        return $extraData;
    }

}
