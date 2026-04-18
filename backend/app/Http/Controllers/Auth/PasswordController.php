<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                'max:64',
                Password::min(6)->mixedCase()->numbers(),
            ],
        ], [
            'password.min' => 'Password must be at least 6 characters.',
            'password.max' => 'Password must not exceed 64 characters.',
        ]);

        $request->user()->update([
            'password' => $validated['password'],
        ]);

        return back();
    }
}
