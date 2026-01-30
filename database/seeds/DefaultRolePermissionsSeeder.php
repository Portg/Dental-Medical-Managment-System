<?php

use Illuminate\Database\Seeder;
use App\Role;
use App\Permission;
use App\RolePermission;

class DefaultRolePermissionsSeeder extends Seeder
{
    public function run()
    {
        // 获取角色
        $superAdmin = Role::where('name', 'Super Administrator')->first();
        $admin = Role::where('name', 'Administrator')->first();
        $doctor = Role::where('name', 'Doctor')->first();
        $nurse = Role::where('name', 'Nurse')->first();
        $receptionist = Role::where('name', 'Receptionist')->first();

        // 超级管理员拥有所有权限
        if ($superAdmin) {
            $allPermissions = Permission::all();
            foreach ($allPermissions as $permission) {
                RolePermission::firstOrCreate([
                    'role_id' => $superAdmin->id,
                    'permission_id' => $permission->id
                ]);
            }
        }

        // 管理员权限
        if ($admin) {
            $adminPermissions = Permission::whereIn('slug', [
                'view-patients', 'create-patients', 'edit-patients',
                'view-appointments', 'create-appointments', 'edit-appointments',
                'view-invoices', 'create-invoices', 'edit-invoices',
                'view-users', 'create-users', 'edit-users',
                'view-branches', 'view-reports', 'export-reports'
            ])->get();

            foreach ($adminPermissions as $permission) {
                RolePermission::firstOrCreate([
                    'role_id' => $admin->id,
                    'permission_id' => $permission->id
                ]);
            }
        }

        // 医生权限
        if ($doctor) {
            $doctorPermissions = Permission::whereIn('slug', [
                'view-patients', 'edit-patients',
                'view-appointments', 'edit-appointments',
                'view-invoices'
            ])->get();

            foreach ($doctorPermissions as $permission) {
                RolePermission::firstOrCreate([
                    'role_id' => $doctor->id,
                    'permission_id' => $permission->id
                ]);
            }
        }

        // 护士权限
        if ($nurse) {
            $nursePermissions = Permission::whereIn('slug', [
                'view-patients', 'edit-patients',
                'view-appointments'
            ])->get();

            foreach ($nursePermissions as $permission) {
                RolePermission::firstOrCreate([
                    'role_id' => $nurse->id,
                    'permission_id' => $permission->id
                ]);
            }
        }

        // 前台权限
        if ($receptionist) {
            $receptionistPermissions = Permission::whereIn('slug', [
                'view-patients', 'create-patients', 'edit-patients',
                'view-appointments', 'create-appointments', 'edit-appointments',
                'view-invoices', 'create-invoices'
            ])->get();

            foreach ($receptionistPermissions as $permission) {
                RolePermission::firstOrCreate([
                    'role_id' => $receptionist->id,
                    'permission_id' => $permission->id
                ]);
            }
        }
    }
}
