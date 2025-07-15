<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;
use App\Models\User;

trait ShortlistRowActionsTrait
{
    // Change admission for a student
    public function changeAdmission(Request $request, $userId)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'session_id' => 'required|exists:course_sessions,id',
        ]);
        $user = User::findOrFail($userId);
        $admission = $user->admissions()->where('course_id', $request->course_id)->first();
        if ($admission) {
            $admission->session = $request->session_id;
            $admission->save();
        } else {
            $user->admissions()->create([
                'course_id' => $request->course_id,
                'session' => $request->session_id,
                'confirmed' => now(),
            ]);
        }
        return response()->json(['message' => 'Admission updated successfully.']);
    }

    // Choose session for a student
    public function chooseSession(Request $request, $userId)
    {
        $request->validate([
            'session_id' => 'required|exists:course_sessions,id',
        ]);
        $user = User::findOrFail($userId);
        $admission = $user->admissions()->first();
        if ($admission) {
            $admission->session = $request->session_id;
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
