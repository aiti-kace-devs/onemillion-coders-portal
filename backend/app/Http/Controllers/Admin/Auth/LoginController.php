<?php

namespace App\Http\Controllers\Admin\Auth;

use Backpack\CRUD\app\Http\Controllers\Auth\LoginController as BackpackLoginController;
use Illuminate\Http\Request;

class LoginController extends BackpackLoginController
{
    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        \Log::info("LoginController::authenticated", [
            'user_id' => $user->id,
            'email' => $user->email,
            'has_role_page_builder' => $user->hasRole('page-builder'),
            'session_id' => $request->session()->getId(),
        ]);

        if ($user->hasRole('page-builder')) {
            \Log::info("LoginController: Redirecting to Statamic CP");
            return redirect()->to(config('statamic.cp.route', 'cp') . '/dashboard');
        }

        \Log::info("LoginController: Redirecting to Backpack dashboard");
        return redirect()->intended($this->redirectPath());
    }
}
