<?php

namespace App\Http\Controllers\Traits;

use App\Http\Requests\ChangeAdmissionRequest;
use App\Http\Requests\ChooseSessionRequest;
use Illuminate\Http\Request;
use App\Models\User;

trait ShortlistRowActionsTrait
{
    // Change admission for a student
    public function changeAdmission(ChangeAdmissionRequest $request, $userId)
    {
        $validated = $request->validated();
        $user = User::findOrFail($userId);
        $admission = $user->admissions()->where('course_id', $validated['course_id'])->first();
        if ($admission) {
            $admission->session = $validated['session_id'];
            $admission->save();
        } else {
            $user->admissions()->create([
                'course_id' => $validated['course_id'],
                'session' => $validated['session_id'],
                'confirmed' => now(),
            ]);
        }
        return response()->json(['message' => 'Admission updated successfully.']);
    }

    // Choose session for a student
    public function chooseSession(ChooseSessionRequest $request, $userId)
    {
        $validated = $request->validated();
        $user = User::findOrFail($userId);
        $admission = $user->admissions()->first();
        if ($admission) {
            $admission->session = $validated['session_id'];
            $admission->save();
        }
        return response()->json(['message' => 'Session chosen successfully.']);
    }

    // Delete admission for a student
    public function deleteAdmission($userId)
    {
        $user = User::findOrFail($userId);
        $user->admissions()->delete();
        return response()->json(['message' => 'Admission deleted successfully.']);
    }
}
