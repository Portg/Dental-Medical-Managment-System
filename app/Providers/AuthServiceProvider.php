<?php

namespace App\Providers;

use App\Permission;
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

        //system access levels
        Gate::define('Super-Administrator-Dashboard', function ($user) {
            if ($user->UserRole->slug === 'super-admin') {
                return true;
            }
            return false;
        });

        Gate::define('Admin-Dashboard', function ($user) {
            if ($user->UserRole->slug === 'admin') {
                return true;
            }
            return false;
        });

        Gate::define('Doctor-Dashboard', function ($user) {
            if ($user->UserRole->slug === 'doctor') {
                return true;
            }
            return false;
        });

        Gate::define('Receptionist-Dashboard', function ($user) {
            if ($user->UserRole->slug === 'receptionist') {
                return true;
            }
            return false;
        });

        Gate::define('Nurse-Dashboard', function ($user) {
            if ($user->UserRole->slug === 'nurse') {
                return true;
            }
            return false;
        });

        //individual records permissions
        Gate::define('action-settings', function ($user, $model) {
            // If user is administrator, then can edit any data
            if ($user->UserRole->slug === 'admin') {
                return true;
            } elseif ($user->id == $model->_who_added) {
                // Check if user is the data author
                return true;
            }

            return true;
        });

        // 动态权限检查 — 使用 Gate::before 避免 boot 阶段查询 DB
        Gate::before(function ($user, $ability) {
            // Super Administrator 跳过所有权限检查
            if ($user->UserRole && $user->UserRole->slug === 'super-admin') {
                return true;
            }

            // 检查用户角色是否拥有该权限 slug（已缓存）
            if ($user->hasPermission($ability)) {
                return true;
            }
        });
    }
}
