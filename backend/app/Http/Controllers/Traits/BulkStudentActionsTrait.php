<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;
use App\Models\User;
use App\Jobs\SendBulkEmailJob;
use App\Jobs\SendBulkSMSJob;

trait BulkStudentActionsTrait
{
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
}
