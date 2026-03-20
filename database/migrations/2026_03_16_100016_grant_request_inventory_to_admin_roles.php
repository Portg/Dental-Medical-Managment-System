<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 超级管理员 和 管理员 需要审批申领单，
 * 补充授予 request-inventory 权限，使侧边栏显示「申领管理」菜单项。
 */
return new class extends Migration
{
    public function up(): void
    {
        $permission = DB::table('permissions')->where('slug', 'request-inventory')->first();
        if (!$permission) {
            return;
        }

        $adminRoles = DB::table('roles')
            ->whereIn('name', ['超级管理员', '管理员'])
            ->pluck('id');

        foreach ($adminRoles as $roleId) {
            $exists = DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permission->id)
                ->exists();

            if (!$exists) {
                DB::table('role_permissions')->insert([
                    'role_id'       => $roleId,
                    'permission_id' => $permission->id,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permission = DB::table('permissions')->where('slug', 'request-inventory')->first();
        if (!$permission) {
            return;
        }

        $adminRoles = DB::table('roles')
            ->whereIn('name', ['超级管理员', '管理员'])
            ->pluck('id');

        DB::table('role_permissions')
            ->whereIn('role_id', $adminRoles)
            ->where('permission_id', $permission->id)
            ->delete();
    }
};
