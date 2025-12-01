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
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Permission-based authorization gates
        Gate::before(function ($user, $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }
        });

        // Define gates dynamically from permissions
        foreach (\App\Permission::all() as $permission) {
            Gate::define($permission->slug, function ($user) use ($permission) {
                return $user->hasPermission($permission->slug);
            });
        }

        //system access levels (legacy support)
        Gate::define('Super-Administrator-Dashboard', function ($user) {
            return $user->hasRole('Super Administrator') ||
                   $user->hasPermission('access-super-admin-dashboard');
        });

        Gate::define('Admin-Dashboard', function ($user) {
            return $user->hasRole('Administrator') ||
                   $user->hasPermission('access-admin-dashboard');
        });

        Gate::define('Doctor-Dashboard', function ($user) {
            return $user->hasRole('Doctor') ||
                   $user->hasPermission('access-doctor-dashboard');
        });

        Gate::define('Receptionist-Dashboard', function ($user) {
            return $user->hasRole('Receptionist') ||
                   $user->hasPermission('access-receptionist-dashboard');
        });

        Gate::define('Nurse-Dashboard', function ($user) {
            return $user->hasRole('Nurse') ||
                   $user->hasPermission('access-nurse-dashboard');
        });

        //individual records permissions
        Gate::define('action-settings', function ($user, $model) {
            // If user is administrator, then can edit any data
            if ($user->isAdmin() || $user->isSuperAdmin()) {
                return true;
            } elseif ($user->id == $model->_who_added) {
                // Check if user is the data author
                return true;
            }

            return false;
        });
    }
}
