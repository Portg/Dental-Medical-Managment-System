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

            // 「能管理」蕴含「能查看」：view-x 被 manage-x 覆盖。
            // 否则每次把只读能力从 manage-x 里拆出来，都得回头给所有已持有
            // manage-x 的角色补发 view-x，漏一个就是一次线上 403。
            if (str_starts_with($ability, 'view-')
                && $user->hasPermission('manage-' . substr($ability, 5))
            ) {
                return true;
            }
        });
    }
}
