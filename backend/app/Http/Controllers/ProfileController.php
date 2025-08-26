<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = User::select('users.*', 'users.updated_at as user_updated', 'users.created_at as user_created', 'users.name as student_name', 'courses.*', 'course_sessions.session as selected_session', 'course_sessions.*', 'user_admission.*')
            ->where('userId', Auth::user()->userId)
            ->join('user_admission', 'user_admission.user_id', '=', 'users.userId')
            ->join('course_sessions', 'user_admission.session', '=', 'course_sessions.id')
            ->join('courses', 'user_admission.course_id', '=', 'courses.id')
            ->first();

        $user['isAdmitted'] = $user?->isAdmitted();
        $user['hasSeparateNameFields'] = $user?->hasSeparateNameFields();

        return Inertia::render('Student/Profile/Edit', [
            'user' => $user
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request)
    {
        $user = $request->user();

        $validated = $request->validated();

        // Handle name fields based on user's current state
        if ($user->hasSeparateNameFields()) {
            // User has separate name fields, update them and sync with name field
            $user->fill($validated);
            $user->setNameFromFields();
        } else {
            // User has only name field, check if they're trying to switch to separate fields
            if (isset($validated['first_name']) && isset($validated['last_name'])) {
                // User is switching to separate name fields
                $user->fill($validated);
                $user->setNameFromFields();
            } else {
                // User is updating the single name field
                $user->fill($validated);

                // If they have a name but no separate fields, offer to parse it
                if (isset($validated['name']) && !empty($validated['name'])) {
                    $user->parseNameIntoFields();
                }
            }
        }

        // Handle previous name tracking
        if ($user->isDirty('name') && !$user->previous_name) {
            $user->previous_name = $user->getOriginal('name');
        }

        if (isset($validated['name']) && $validated['name'] == $user->previous_name) {
            $user->previous_name = null;
        }

        $user->details_updated_at = now();

        $user->save();

        return Redirect::route('student.profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
