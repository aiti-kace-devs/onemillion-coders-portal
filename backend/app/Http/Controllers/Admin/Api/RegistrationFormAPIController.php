<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Form;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RegistrationFormAPIController extends Controller
{
    public function index(Request $request)
    {
        $forms = Form::query()->get()->map(function (Form $form) {
            $schema = $this->normalizeSchema($form->schema ?? []);

            return [
                'id' => $form->id,
                'title' => $form->title,
                'uuid' => $form->uuid,
                'schema' => $schema,
                'grouped_schema' => $this->buildGroupedSchema($schema),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $forms,
        ]);
    }

    private function normalizeSchema(array $schema): array
    {
        return collect($schema)
            ->filter(fn ($field) => is_array($field))
            ->map(function (array $field) {
                $title = isset($field['title']) ? trim((string) $field['title']) : null;
                $fieldName = ! empty($field['field_name'])
                    ? $field['field_name']
                    : ($title ? Str::slug(strtolower($title), '_') : null);
                $validators = is_array($field['validators'] ?? null) ? $field['validators'] : [];

                return [
                    'title' => $title,
                    'type' => $this->nullableString($field['type'] ?? null),
                    'description' => $this->nullableString($field['description'] ?? null),
                    'placeholder' => $this->nullableString($field['placeholder'] ?? null),
                    'group_name' => $this->nullableString($field['group_name'] ?? null),
                    'rules' => $this->nullableString($field['rules'] ?? null),
                    'options' => $this->nullableString($field['options'] ?? null),
                    'validators' => [
                        'required' => filter_var($validators['required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'unique' => filter_var($validators['unique'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    ],
                    'field_name' => $fieldName,
                ];
            })
            ->values()
            ->all();
    }

    private function buildGroupedSchema(array $schema): array
    {
        $groupedSchema = [];

        foreach ($schema as $field) {
            $groupTitle = $field['group_name'] ?? 'Other';

            if (! isset($groupedSchema[$groupTitle])) {
                $groupedSchema[$groupTitle] = [
                    'title' => $groupTitle,
                    'fields' => [],
                ];
            }

            $groupedField = $field;
            unset($groupedField['group_name'], $groupedField['placeholder']);

            $groupedSchema[$groupTitle]['fields'][] = $groupedField;
        }

        return array_values($groupedSchema);
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    public function check_user_by_userID($userID)
    {
        $user = User::where('userId', $userID)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        if (is_null($user->registered_course)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'registered_course' => null,
                    'student_level' => $user->student_level,
                    'userId' => $user->userId,
                    'support' => $user->support,
                ],
            ]);
        }

        if (! config('ALLOW_COURSE_CHANGE', false)) {
            return response()->json([
                'success' => false,
                'message' => 'Course change is not allowed at this time. Please contact the administrators.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'registered_course' => $user->registered_course,
                'student_level' => $user->student_level,
                'userId' => $user->userId,
            ],
        ]);
    }

    public function confirmCourse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,userId',
            'course_id' => 'required|integer|exists:courses,id',
            'centre_id' => 'required|integer|exists:centres,id',
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

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ]);
        }

        $course = Course::with('programme')
            ->where('id', $data['course_id'])
            ->where('centre_id', $data['centre_id'])
            ->first();
        if (! $course) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found for the selected centre.',
            ], 404);
        }

        $supportRequested = filter_var($data['support'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $centreIsReady = (bool) ($course->centre?->is_ready);

        if ($supportRequested && ! $centreIsReady) {
            return response()->json([
                'success' => false,
                'message' => 'Resource (internet & laptop) support is not available for the selected centre at this time. You can try again later',
            ], 422);
        }

        $user->registered_course = $course->id;
        $user->shortlist = true;
        $user->support = filter_var($data['support'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $user->save();

        return response()->json([
            'success' => true,
            'data' => [
                // 'user' => $user->fresh(),
                // 'course' => $course,
                // 'already_registered' => (int) $user->registered_course === (int) $course->id,
                'message' => 'Course registration confirmed successfully.',
            ],
        ]);
    }




        public function switchToSelfPaced(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,userId'
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

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ]);
        }

        $user->support = false;
        $user->save();

        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'You have successfully switched to self-paced learning. Resource support has been removed from your registration.',
            ],
        ]);
    }





        public function enrollSelfPacedStudent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,userId',
            'course_id' => 'required|integer|exists:courses,id',
            'centre_id' => 'required|integer|exists:centres,id',
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

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ]);
        }

        $course = Course::with('programme')
            ->where('id', $data['course_id'])
            ->where('centre_id', $data['centre_id'])
            ->first();
        if (! $course) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found for the selected centre.',
            ], 404);
        }

        $user->registered_course = $course->id;
        $user->save();

        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'Course registration confirmed successfully.',
            ],
        ]);
    }

}
