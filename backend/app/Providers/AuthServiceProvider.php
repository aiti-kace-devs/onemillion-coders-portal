<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user, $ability) {
            $user = backpack_user();

            return $user->hasRole('super-admin') ? true : null;
        });

        // add a policy to allow 'access cp' policy if role is super-admin or page-builder
        Gate::before(function ($user, $ability) {
            $user = backpack_user();
            if ('access cp' == $ability && $user->hasRole('page-builder')) {
                return true;
            }
        });
    }
}
