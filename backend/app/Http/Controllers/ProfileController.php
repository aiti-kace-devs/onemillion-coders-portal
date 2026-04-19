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
        $user = $request->user()->load(['admission.course', 'admission.courseSession', 'admission.booking.session', 'admission.programmeBatch']);
        $verificationStatus = app(GhanaCardService::class)->buildStatus($user);

        $userData = array_merge($user->toArray(), [
            'isAdmitted' => $user->isAdmitted(),
            'student_name' => $user->student_name,
            'course_name' => $user->course_name,
            'selected_session' => $user->selected_session,
            'session_dates' => $user->session_dates,
            'session_time' => $user->session_time_value,
            'session_name' => $user->session_name,
            'validity_period' => $user->validity_period,
            'verification_date' => $user->verification_date,
            'ghcard_verified' => (bool) data_get($verificationStatus, 'verified', false),
            'ghcard_verification_status' => data_get($verificationStatus, 'verified', false) ? 'verified' : 'pending',
            'ghcard_latest_attempt' => data_get($verificationStatus, 'latest_attempt'),
        ]);

        $userData = collect($userData)->only([
            'id',
            'userId',
            'student_id',
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
            'session_dates',
            'session_time',
            'session_name',
            'validity_period',
            'verification_date',
            'ghcard_verified',
            'ghcard_verification_status',
            'ghcard_latest_attempt',
            'ghcard_image_url',
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

        $user->network_type = $validated['network_type'];
        $user->mobile_no = $validated['mobile_no'];
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
