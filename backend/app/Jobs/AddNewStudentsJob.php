<?php

namespace App\Jobs;

use App\Events\UserRegistered;
use App\Helpers\GoogleSheets;
use App\Helpers\SmsHelper;
use App\Jobs\SendExamLoginCredentialsJob;
use App\Jobs\SendSMSAfterRegistrationJob;
use App\Models\Oex_exam_master;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Models\user_exam;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AddNewStudentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $students;

    public function __construct($students)
    {
        $this->students = $students;
    }

    public function handle()
    {
        $errors = [];

        foreach ($this->students as $student) {
            $validator = Validator::make($student, [
                'name' => 'nullable',
                'first_name' => 'required',
                'middle_name' => 'nullable',
                'last_name' => 'required',
                'email' => 'required|email',
                'mobile_no' => 'required',
                'gender' => 'required',
                'exam' => 'required_if:exam_name,null|exists:oex_exam_masters,id',
                // 'registered_course' => 'nullable|exists:courses,id',
                'age' => 'required',
                'userId' => 'required',
                'password' => 'sometimes',
                'exam_name' => 'sometimes',
                'ghcard' => 'nullable',
                'form_response_id' => 'required',
                'data' => 'nullable|array',
                'pwd' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                $errors[] = "Error creating student with email " . $student['email'];
                $errors[] = $validator->errors()->all();
                continue;
            }

            $plainPassword = $student['password'] ?? str()->random(8);

            // Exam validation
            if (!empty($student['exam_name'])) {
                $exam = null;
                if (strtolower($student['exam_name']) == 'random') {
                    $exam = Oex_exam_master::inRandomOrder()->first();
                } else {
                    $exam = Oex_exam_master::where('title', $student['exam_name'])->first();
                }
                if ($exam == null) {
                    $errors[] = "Error creating student with email " . $student['email'] . ": Exam not found";
                    continue;
                }
                $student['exam'] = $exam->id;
            }

            // Check for existing user
            $existingUser = User::where('email', $student['email'])->first();
            if ($existingUser == null) {
                // Create a new student
                $std = new User();
                $std->name = $student['name'];
                $std->first_name = $student['first_name'];
                $std->middle_name = $student['middle_name'];
                $std->last_name = $student['last_name'];
                $std->email = $student['email'];
                $std->mobile_no = $student['mobile_no'];
                $std->exam = $student['exam'];
                $std->userId = $student['userId'];
                $std->password = $plainPassword;
                // $std->registered_course = !empty($student['registered_course']) ? $student['registered_course'] : null;
                $std->data = $student['data'] ?? null;
                $std->age  = $student['age'];
                $std->gender = $student['gender'];
                $std->pwd = (bool) ($student['pwd'] ?? false);
                $std->status = 1;
                $std->ghcard = $student['ghcard'] ?? null;
                $std->form_response_id = $student['form_response_id'];
                $std->save();

                UserRegistered::dispatch($std, $plainPassword);
            }

            // Create or update user_exam
            user_exam::firstOrCreate(
                [
                    'user_id' => $std->id,
                    'exam_id' => $student['exam'],
                ],
                [
                    'user_id' => $std->id,
                    'exam_id' => $student['exam'],
                    'std_status' => 1,
                    'exam_joined' => 0,
                ]
            );

            if ((bool) config('SEND_SMS_AFTER_REGISTRATION', true)) {
                $smsContent = SmsHelper::getTemplate(
                    'AFTER_REGISTRATION_SMS',
                    [
                        'name' => $student['name'],
                    ]
                ) ?? '';

                if (!empty($smsContent)) {
                    SendSMSAfterRegistrationJob::dispatch([
                        'message' => $smsContent,
                        'phonenumber' => $student['mobile_no'],
                    ]);
                }
            }

        }

        if (!empty($errors)) {
            Log::error($errors);
        }
    }
}
