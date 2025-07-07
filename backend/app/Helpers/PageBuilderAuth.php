<?php

namespace App\Helpers;

use PHPageBuilder\Contracts\AuthContract;

class PageBuilderAuth implements AuthContract
{
    /**
     * Process the current GET or POST request and redirect or render the requested page.
     *
     * @param $action
     */
    public function handleRequest($action)
    {

        if (phpb_in_module('auth')) {
            return redirect()->action('App\Http\Controllers\Admin\AuthenticatedSessionController@create');
            // if ($action === 'login') {
            //     if ($_POST['username'] === phpb_config('auth.username') && $_POST['password'] === phpb_config('auth.password')) {
            //         $_SESSION['phpb_logged_in'] = true;
            //         phpb_redirect(phpb_url('website_manager'));
            //     } else {
            //         phpb_redirect(phpb_url('website_manager'), [
            //             'message-type' => 'warning',
            //             'message' => phpb_trans('auth.invalid-credentials')
            //         ]);
            //     }
            // } elseif ($action === 'logout') {
            //     return redirect()->action('App\Http\Controllers\Admin\AuthenticatedSessionController@destroy');
            // }
        }
    }

    /**
     * Return whether the current request has an authenticated session.
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return auth('admin')->check();
    }

    /**
     * If the user is not authenticated, show the login form.
     */
    public function requireAuth()
    {
        if (! $this->isAuthenticated()) {
            return redirect(url('addmin/login'));
        }
    }

    /**
     * Render the login form.
     */
    public function renderLoginForm()
    {
        $viewFile = 'login-form';
        require __DIR__ . '/resources/views/layout.php';
    }
}
