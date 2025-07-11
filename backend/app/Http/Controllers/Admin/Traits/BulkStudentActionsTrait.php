<?php

namespace App\Http\Controllers\Admin\Traits;

use Illuminate\Http\Request;
use App\Models\User;
use App\Jobs\SendBulkEmailJob;
use App\Jobs\SendBulkSMSJob;

trait BulkStudentActionsTrait
{
    public function sendBulkEmail(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required',
            'message' => 'sometimes',
            'template' => 'required_if:message,null',
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:users,id',
        ]);
        SendBulkEmailJob::dispatch($validated);
        return response()->json(['message' => 'Emails sending initiated!']);
    }

    public function sendBulkSMS(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:users,id',
        ]);
        SendBulkSMSJob::dispatch($validated);
        return response()->json(['message' => 'SMS sending initiated!']);
    }

    public function saveShortlistedStudents(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'numeric|exists:users,id',
        ]);
        $updatedCount = User::whereIn('id', $request->student_ids)
            ->where(function ($q) {
                $q->whereNull('shortlist')->orWhere('shortlist', '!=', 1);
            })
            ->update(['shortlist' => 1]);
        return response()->json(['message' => "$updatedCount user(s) successfully shortlisted."]);
    }
}
