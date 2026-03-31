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
        $user = Auth::user();
        $userData = $user->only([
            'id',
            'userId',
            'name',
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
            'registered_course'
        ]);

        $userData['isAdmitted'] = $user->isAdmitted();

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
