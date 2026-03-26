<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Programme;
use App\Models\CourseCategory;
use App\Models\Branch;
use App\Models\Form;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\UserAdmission;
use Illuminate\Support\Facades\Validator;

class RegistrationFormAPIController extends Controller
{




    public function index(Request $request)
    {
        $groupConfig = [

            'Basic Information' => [
                'first_name',
                'last_name',
                'middle_name',
                'age',
                'gender',
                'do_you_require_any_special_support_for_your_training'
            ],

            'Verification and Identification' => [
                'email',
                'phone',
                'ghana_card_number'
            ],

            'Educational Information' => [
                'highest_level_of_education',
                'certificate',
            ],
        ];


        $form = Form::all()->map(function (Form $form) use ($groupConfig) {
            $schema = collect($form->schema ?? [])->map(function ($field) {
                if (empty($field['field_name']) && ! empty($field['title'])) {
                    $field['field_name'] = Str::slug(strtolower($field['title']), '_');
                }

                return $field;
            });

            $schemaByName = $schema->keyBy('field_name');
            $assigned = [];

            $grouped = collect($groupConfig)
                ->map(function (array $fieldNames, string $groupTitle) use ($schemaByName, &$assigned) {
                    $fields = collect($fieldNames)
                        ->map(function (string $fieldName) use ($schemaByName, &$assigned) {
                            if (! $schemaByName->has($fieldName)) {
                                return null;
                            }

                            $assigned[] = $fieldName;
                            return $schemaByName->get($fieldName);
                        })
                        ->filter()
                        ->values();

                    return [
                        'title' => $groupTitle,
                        'fields' => $fields,
                    ];
                })
                ->filter(fn (array $group) => $group['fields']->isNotEmpty())
                ->values();

            $ungrouped = $schema
                ->filter(function (array $field) use ($assigned) {
                    $name = $field['field_name'] ?? null;
                    return $name && ! in_array($name, $assigned, true);
                })
                ->values();

            if ($ungrouped->isNotEmpty()) {
                $grouped->push([
                    'title' => 'Other',
                    'fields' => $ungrouped,
                ]);
            }

            $form->setAttribute('grouped_schema', $grouped);

            return $form;
        });

        return response()->json([
            'success' => true,
            'data' => $form
        ]);

    }




    public function check_user_by_userID($userID)
    {
        $user = User::where('userId', $userID)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if (is_null($user->registered_course)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'registered_course' => null,
                    'student_level' => $user->student_level,
                    'userId' => $user->userId,
                ]
            ]);
        }

        if (!config('ALLOW_COURSE_CHANGE', false)) {
            return response()->json([
                'success' => false,
                'message' => 'Course change is not allowed at this time. Please contact the administrators.'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'registered_course' => $user->registered_course,
                'student_level' => $user->student_level,
                'userId' => $user->userId,
            ]
        ]);
    }


    public function confirmCourse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,userId',
            'course_id' => 'required|integer|exists:courses,id',
            'support' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $user = User::where('userId', $data['userId'])->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ]);
        }
        
        $course = Course::with('programme')->find($data['course_id']);
        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], 404);
        }

        $user->registered_course = $course->id;
        $user->support = filter_var($data['support'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $user->save();

        return response()->json([
            'success' => true,
            'data' => [
                // 'user' => $user->fresh(),
                // 'course' => $course,
                // 'already_registered' => (int) $user->registered_course === (int) $course->id,
                'message' => 'Course registration confirmed successfully.'
            ],
        ]);
    }





    //     public function check_user_by_userID($userID)
    // {
    //     $user = User::where('userId', $userID)->first();

    //     if (!$user) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'User not found'
    //         ], 404);
    //     }

    //     $admission = UserAdmission::where('user_id', $userID)
    //         ->whereNotNull('confirmed')
    //         ->first();

    //     if (!$admission) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'User admission not confirmed'
    //         ]);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'User admission confirmed',
    //         'data' => [
    //             'user' => $user,
    //             'admission' => $admission
    //         ]
    //     ]);
    // }





}
