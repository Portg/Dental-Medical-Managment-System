<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('permissions')->where('slug', 'manage-menu-items')->exists();

        if (!$exists) {
            DB::table('permissions')->insert([
                'name'        => 'Manage Menu Items',
                'slug'        => 'manage-menu-items',
                'module'      => 'Settings',
                'description' => 'Can manage menu items and menu structure',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // 分配给 Super Administrator
        $permId = DB::table('permissions')->where('slug', 'manage-menu-items')->value('id');
        $roleId = DB::table('roles')->where('slug', 'super-admin')->value('id');

        if ($permId && $roleId) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id'       => $roleId,
                'permission_id' => $permId,
            ]);
        }
    }

    public function down(): void
    {
        $permId = DB::table('permissions')->where('slug', 'manage-menu-items')->value('id');

        if ($permId) {
            DB::table('role_permissions')->where('permission_id', $permId)->delete();
            DB::table('permissions')->where('id', $permId)->delete();
        }
    }
};
