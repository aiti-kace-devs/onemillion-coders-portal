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

        if ($user->hasRole('page-builder') && $user->roles()->count() == 1) {
            return redirect()->to(config('statamic.cp.route', 'cp') . '/dashboard');
        }

        return redirect()->intended($this->redirectPath());
    }
}
