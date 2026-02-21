<?php

use App\Permission;
use App\Role;
use App\RolePermission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate([
            'slug' => 'manage-system-maintenance',
        ], [
            'name'        => 'Manage System Maintenance',
            'module'      => 'Settings',
            'description' => 'Can access system maintenance page (backups, retention, logs)',
        ]);

        // Super Administrator gets all permissions via Gate::before, but seed for DB consistency
        $superAdmin = Role::where('name', 'Super Administrator')->first();
        if ($superAdmin) {
            RolePermission::firstOrCreate([
                'role_id'       => $superAdmin->id,
                'permission_id' => $permission->id,
            ]);
        }

        $admin = Role::where('name', 'Administrator')->first();
        if ($admin) {
            RolePermission::firstOrCreate([
                'role_id'       => $admin->id,
                'permission_id' => $permission->id,
            ]);
        }
    }

    public function down(): void
    {
        $permission = Permission::where('slug', 'manage-system-maintenance')->first();
        if ($permission) {
            RolePermission::where('permission_id', $permission->id)->delete();
            $permission->delete();
        }
    }
};
