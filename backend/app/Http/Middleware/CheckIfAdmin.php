<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;

class CheckIfAdmin
{
    /**
     * Checked that the logged in user is an administrator.
     *
     * --------------
     * VERY IMPORTANT
     * --------------
     * If you have both regular users and admins inside the same table, change
     * the contents of this method to check that the logged in user
     * is an admin, and not a regular user.
     *
     * Additionally, in Laravel 7+, you should change app/Providers/RouteServiceProvider::HOME
     * which defines the route where a logged in user (but not admin) gets redirected
     * when trying to access an admin route. By default it's '/home' but Backpack
     * does not have a '/home' route, use something you've built for your users
     * (again - users, not admins).
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @return bool
     */
    private function checkIfUserIsAdmin(Admin $user)
    {
        $isSuper = $user->isSuper();
        $hasRoles = $user->roles()->exists();

        \Log::info("Admin Auth Check", [
            'email' => $user->email,
            'is_super' => $isSuper,
            'has_roles' => $hasRoles,
            'user_id' => $user->id
        ]);

        return $isSuper || $hasRoles;
    }

    /**
     * Answer to unauthorized access request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    private function respondToUnauthorizedRequest($request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response(trans('backpack::base.unauthorized'), 401);
        } else {
            // Log the user out to break the infinite redirect loop
            if (backpack_auth()->check()) {
                backpack_auth()->logout();
            }

            return redirect()->guest(backpack_url('login'))->withErrors([
                'email' => 'You do not have permission to access the admin area.'
            ]);
        }
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        \Log::info("CheckIfAdmin::handle START", [
            'url' => $request->fullUrl(),
            'is_guest' => backpack_auth()->guest(),
            'session_id' => $request->session()->getId(),
        ]);

        if (backpack_auth()->guest()) {
            \Log::info("CheckIfAdmin: User is GUEST, redirecting to login");
            return $this->respondToUnauthorizedRequest($request);
        }

        $user = backpack_user();
        \Log::info("CheckIfAdmin: User authenticated", [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        if (! $this->checkIfUserIsAdmin($user)) {
            \Log::info("CheckIfAdmin: User FAILED admin check, logging out");
            return $this->respondToUnauthorizedRequest($request);
        }

        \Log::info("CheckIfAdmin: User PASSED admin check, proceeding");
        return $next($request);
    }
}
