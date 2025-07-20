<?php

namespace App\Http\Controllers\Traits;

use App\Models\SmsTemplate;
use Illuminate\Http\Request;
use App\Models\User;
use App\Jobs\SendBulkEmailJob;
use App\Jobs\SendBulkSMSJob;

trait BulkStudentActionsTrait
{
    public function fetchSmsTemplate()
    {
        // Fetch the templates
        $templates = SmsTemplate::select('id', 'name', 'content')->get();

        return response()->json($templates);
    }

    public function sendBulkEmail(Request $request)
    {
        $validated = $request->validate(
            [
                'subject' => 'required',
                'message' => 'sometimes',
                'template' => 'required_if:message,null',
                'student_ids' => 'required_if:list,null|nullable|array',
                'student_ids.*' => 'exists:users,id',
                'list' => 'required_if:student_ids,null|nullable|string',
            ],
            [],
            [
                'student_ids.*' => 'student',
            ],
        );

        // if no list_name or students_id
        if (empty($validated['list']) && empty($validated['student_ids'])) {
            return redirect()
                ->back()
                ->with([
                    'flash' => 'No students/ list selected.',
                    'key' => 'error',
                ]);
        }

        SendBulkEmailJob::dispatch($validated);

        return response()->json([
            'flash' => 'Email sending initiated successfully!',
            'key' => 'success',
        ]);
    }

    public function sendBulkSMS(Request $request)
    {
        $validated = $request->validate(
            [
                'message' => 'required|string',
                'student_ids' => 'sometimes|nullable|array',
                'student_ids.*' => 'exists:users,id',
                'list' => 'required_if:student_ids,null|nullable|string',
            ],
            [],
            [
                'student_ids.*' => 'student',
            ],
        );

        if (empty($validated['list']) && empty($validated['student_ids'])) {
            return redirect()
                ->back()
                ->with([
                    'flash' => 'No students/ list selected.',
                    'key' => 'error',
                ]);
        }

        SendBulkSMSJob::dispatch($validated);

        return response()->json([
            'flash' => 'SMS sending initiated successfully!',
            'key' => 'success',
        ]);
    }

    public function saveShortlistedStudents(Request $request)
    {
        $request->validate(
            [
                'emails' => 'sometimes|array',
                'emails.*' => 'email',
                'student_ids' => 'sometimes|array',
                'student_ids.*' => 'numeric',
                'phone_numbers' => 'sometimes|array',
                // 'phone_numbers.*' => 'phone'
            ],
            [],
            [
                'emails.*' => 'email address',
                'student_ids.*' => 'student',
            ],
        );
        if (empty($request->input('emails')) && empty($request->input('student_ids')) && empty($request->input('phone_numbers'))) {
            return response()->json(
                [
                    'message' => 'Email(s), Student ID(s), or PhoneNumber(s) are required.',
                ],
                400,
            );
        }

        $data = $request->input('emails') ?? ($request->input('student_ids') ?? $request->input('phone_numbers'));
        $columnName = $request->has('emails') ? 'email' : ($request->has('phone_numbers') ? 'mobile_no' : 'id');

        $usersToUpdate = User::whereIn($columnName, (array) $data)
            ->where(function ($query) {
                $query->whereNull('shortlist')->orWhere('shortlist', '!=', 1);
            })
            ->get();

        if ($usersToUpdate->isEmpty()) {
            return response()->json(
                [
                    'message' => 'No users found to update or all are already shortlisted.',
                ],
                404,
            );
        }

        $updatedCount = User::whereIn('id', $usersToUpdate->pluck('id'))->update(['shortlist' => 1]);

        return response()->json([
            'message' => "$updatedCount user(s) successfully shortlisted.",
        ]);
    }

    public function admitStudent(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|nullable|exists:courses,id',
            'session_id' => 'sometimes|nullable|exists:course_sessions,id',
            'user_id' => 'sometimes|nullable|required_if:user_ids,null|exists:users,userId',
            'change' => 'sometimes',
            'user_ids' => 'sometimes|nullable|required_if:user_id,null|array',
            'user_ids.*' => 'exists:users,userId',
        ]);

        $course = !empty($validated['course_id']) ? \App\Models\Course::find($validated['course_id']) : null;
        $session = !empty($validated['session_id']) ? \App\Models\CourseSession::find($validated['session_id']) : null;
        $change = ($validated['change'] ?? false) == 'true';

        if ($session && isset($session->course_id) && $course && isset($course->id) && $session->course_id != $course->id) {
            return response()->json([
                'success' => false,
                'message' => 'Session not valid for selected course',
            ], 422);
        }
        $message = 'Student(s) admitted successfully';
        $admittedCount = 0;

        if ($validated['user_id'] ?? false) {
            $user_id = $validated['user_id'];
            $user = User::where('userId', $user_id)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => "Student with ID $user_id not found."
                ], 404);
            }
            \App\Jobs\CreateStudentAdmissionJob::dispatch($user, $course, $session);
            $admittedCount = 1;
            $oldAdmission = \App\Models\UserAdmission::where('user_id', $user_id)->first();
            if ($oldAdmission && $change) {
                $message = 'Student admission changed successfully';
            }
        } elseif (count($validated['user_ids'] ?? []) > 0) {
            $user_ids = $validated['user_ids'];
            foreach ($user_ids as $user_id) {
                $user = \App\Models\User::where('userId', $user_id)->first();
                if ($user) {
                    \App\Jobs\CreateStudentAdmissionJob::dispatch($user, $course, $session);
                    $admittedCount++;
                }
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No user(s) provided.'
            ], 400);
        }
        return response()->json([
            'success' => true,
            'message' => $message,
            'admitted_count' => $admittedCount,
        ]);
    }
}
