<?php

namespace App\Http\Controllers\Traits;

use App\Http\Requests\SaveShortlistedStudentsRequest;
use App\Http\Requests\SendBulkEmailRequest;
use App\Http\Requests\SendBulkSMSRequest;
use App\Jobs\CreateStudentAdmissionJob;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\SmsTemplate;
use App\Models\UserAdmission;
use Illuminate\Http\Request;
use App\Models\User;
use App\Jobs\SendBulkEmailJob;
use App\Jobs\SendBulkSMSJob;

trait BulkStudentActionsTrait
{
    use GetsFilteredQuery;

    public function fetchSmsTemplate()
    {
        // Fetch the templates
        $templates = SmsTemplate::select('id', 'name', 'content')->get();

        return response()->json($templates);
    }

    public function sendBulkEmail(SendBulkEmailRequest $request)
    {
        $validated = $request->validated();

        if ($request->has('select_all_in_query')) {
            $query = $this->getFilteredQuery($request);
            $validated['student_ids'] = $query->pluck('id')->toArray();
        }

        if (empty($validated['student_ids'])) {
            return response()->json(
                [
                    'message' => 'No students selected.',
                ],
                422,
            );
        }

        SendBulkEmailJob::dispatch($validated);

        return response()->json([
            'message' => 'Email sending initiated successfully!',
        ]);
    }

    public function sendBulkSMS(SendBulkSMSRequest $request)
    {
        $validated = $request->validated();

        if ($request->has('select_all_in_query')) {
            $query = $this->getFilteredQuery($request);
            $validated['student_ids'] = $query->pluck('id')->toArray();
        }

        if (empty($validated['student_ids'])) {
            return response()->json(
                [
                    'message' => 'No students selected.',
                ],
                422,
            );
        }

        SendBulkSMSJob::dispatch($validated);

        return response()->json([
            'message' => 'SMS sending initiated successfully!',
        ]);
    }

    public function saveShortlistedStudents(SaveShortlistedStudentsRequest $request)
    {
        if ($request->has('select_all_in_query')) {
            $query = $this->getFilteredQuery();
            $usersToUpdate = $query
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

        $validated = $request->validated();
        if (empty($validated['emails']) && empty($validated['student_ids']) && empty($validated['phone_numbers'])) {
            return response()->json(
                [
                    'message' => 'Email(s), Student ID(s), or PhoneNumber(s) are required.',
                ],
                400,
            );
        }

        $data = $validated['emails'] ?? ($validated['student_ids'] ?? $validated['phone_numbers']);
        $columnName = isset($validated['emails']) ? 'email' : (isset($validated['phone_numbers']) ? 'mobile_no' : 'id');

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
        if ($request->has('select_all_in_query')) {
            $query = $this->getFilteredQuery();
            $request->merge(['user_ids' => $query->pluck('userId')->toArray()]);
        }
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'session_id' => 'sometimes|nullable|exists:course_sessions,id',
            'user_id' => 'sometimes|nullable|required_if:user_ids,null|exists:users,id',
            'change' => 'sometimes',
            'user_ids' => 'sometimes|nullable|required_if:user_id,null|array',
            'user_ids.*' => 'exists:users,id',
        ]);
        $admittedCount = 0;
        $course = Course::find($validated['course_id']);
        $session = CourseSession::find($validated['session_id'] ?? '');
        $change = $validated['change'] == 'true';

        if ($session && $session->course_id != $course->id) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not valid for selected course',
                ], 422);
            }
            return redirect()
                ->back()
                ->with([
                    'flash' => 'Session not valid for selected course',
                    'key' => 'error',
                ]);
        }
        $message = 'Student(s) admitted successfully';

        if ($validated['user_id'] ?? false) {
            $user = User::find($validated['user_id']);

            if (!$user) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Student not found.',
                    ],
                    404,
                );
            }

            // ensure student is shortlisted before/when admitting
            if (!$user->shortlist) {
                $user->shortlist = 1;
                $user->save();
            }

            // Now use the userId field for operations
            $user_id = $user->userId;
            CreateStudentAdmissionJob::dispatch($user, $course, $session);
            $admittedCount = 1;
            $oldAdmission = UserAdmission::where('user_id', $user_id)->first();
            if ($oldAdmission && $change) {
                $message = 'Student admission changed successfully';
            }
        } elseif (count($validated['user_ids'] ?? []) > 0) {
            $users = User::whereIn('id', $validated['user_ids'])->get();
            foreach ($users as $user) {
                // ensure student is shortlisted before/when admitting
                if (!$user->shortlist) {
                    $user->shortlist = 1;
                    $user->save();
                }

                $user_id = $user->userId;
                CreateStudentAdmissionJob::dispatch($user, $course, $session);
                $admittedCount++;
            }
        } else {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'No user(s) provided.',
                ],
                400,
            );
        }

        // Return JSON for AJAX requests, redirect for regular requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'admitted_count' => $admittedCount,
            ]);
        }

        return redirect()
            ->back()
            ->with([
                'flash' => $message,
                'key' => 'success',
            ]);
    }
}
