<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Services\GhanaCardService;
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
        $user = $request->user()->load(['admission.course', 'admission.courseSession']);
        $verificationStatus = app(GhanaCardService::class)->buildStatus($user);

        $userData = array_merge($user->toArray(), [
            'isAdmitted' => $user->isAdmitted(),
            'student_name' => $user->student_name,
            'course_name' => $user->course_name,
            'selected_session' => $user->selected_session,
            'verification_date' => $user->verification_date,
            'ghcard_verified' => (bool) data_get($verificationStatus, 'verified', false),
            'ghcard_verification_status' => data_get($verificationStatus, 'verified', false) ? 'verified' : 'pending',
            'ghcard_latest_attempt' => data_get($verificationStatus, 'latest_attempt'),
        ]);

        $userData = collect($userData)->only([
            'id',
            'userId',
            'name',
            'student_name',
            'first_name',
            'middle_name',
            'last_name',
            'email',
            'mobile_no',
            'gender',
            'network_type',
            'card_type',
            'ghcard',
            'age',
            'pwd',
            'details_updated_at',
            'registered_course',
            'course_name',
            'selected_session',
            'verification_date',
            'ghcard_verified',
            'ghcard_verification_status',
            'ghcard_latest_attempt',
            'isAdmitted'
        ])->toArray();

        return Inertia::render('Student/Profile/Edit', [
            'user' => $userData
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        // Always update the separate name fields
        $user->fill($validated);

        // Always sync the name field with the separate fields
        $user->setNameFromFields();

        // Handle previous name tracking
        if ($user->isDirty('name') && !$user->previous_name) {
            $user->previous_name = $user->getOriginal('name');
        }

        if (isset($validated['name']) && $validated['name'] == $user->previous_name) {
            $user->previous_name = null;
        }

        $user->details_updated_at = now();
        $user->save();

        // activity('student')
        //     ->causedBy($user)
        //     ->event('Profile Modified')
        //     ->log("{$user->name} successfully modified their profile.");

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
