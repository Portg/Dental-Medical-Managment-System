<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Permission;

class PermissionsTableSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // 患者管理权限
            ['name' => 'View Patients', 'slug' => 'view-patients', 'module' => 'Patients', 'description' => 'Can view patient list'],
            ['name' => 'Create Patients', 'slug' => 'create-patients', 'module' => 'Patients', 'description' => 'Can create new patients'],
            ['name' => 'Edit Patients', 'slug' => 'edit-patients', 'module' => 'Patients', 'description' => 'Can edit patient information'],
            ['name' => 'Delete Patients', 'slug' => 'delete-patients', 'module' => 'Patients', 'description' => 'Can delete patients'],

            // 预约管理权限
            ['name' => 'View Appointments', 'slug' => 'view-appointments', 'module' => 'Appointments', 'description' => 'Can view appointments'],
            ['name' => 'Create Appointments', 'slug' => 'create-appointments', 'module' => 'Appointments', 'description' => 'Can create appointments'],
            ['name' => 'Edit Appointments', 'slug' => 'edit-appointments', 'module' => 'Appointments', 'description' => 'Can edit appointments'],
            ['name' => 'Delete Appointments', 'slug' => 'delete-appointments', 'module' => 'Appointments', 'description' => 'Can delete appointments'],

            // 发票管理权限
            ['name' => 'View Invoices', 'slug' => 'view-invoices', 'module' => 'Invoices', 'description' => 'Can view invoices'],
            ['name' => 'Create Invoices', 'slug' => 'create-invoices', 'module' => 'Invoices', 'description' => 'Can create invoices'],
            ['name' => 'Edit Invoices', 'slug' => 'edit-invoices', 'module' => 'Invoices', 'description' => 'Can edit invoices'],
            ['name' => 'Delete Invoices', 'slug' => 'delete-invoices', 'module' => 'Invoices', 'description' => 'Can delete invoices'],

            // 用户管理权限
            ['name' => 'View Users', 'slug' => 'view-users', 'module' => 'Users', 'description' => 'Can view system users'],
            ['name' => 'Create Users', 'slug' => 'create-users', 'module' => 'Users', 'description' => 'Can create system users'],
            ['name' => 'Edit Users', 'slug' => 'edit-users', 'module' => 'Users', 'description' => 'Can edit system users'],
            ['name' => 'Delete Users', 'slug' => 'delete-users', 'module' => 'Users', 'description' => 'Can delete system users'],

            // 角色权限管理
            ['name' => 'Manage Roles', 'slug' => 'manage-roles', 'module' => 'Settings', 'description' => 'Can manage roles'],
            ['name' => 'Manage Permissions', 'slug' => 'manage-permissions', 'module' => 'Settings', 'description' => 'Can manage permissions'],
            ['name' => 'Manage Role Permissions', 'slug' => 'manage-role-permissions', 'module' => 'Settings', 'description' => 'Can assign permissions to roles'],

            // 机构管理权限
            ['name' => 'View Branches', 'slug' => 'view-branches', 'module' => 'Branches', 'description' => 'Can view branches'],
            ['name' => 'Create Branches', 'slug' => 'create-branches', 'module' => 'Branches', 'description' => 'Can create branches'],
            ['name' => 'Edit Branches', 'slug' => 'edit-branches', 'module' => 'Branches', 'description' => 'Can edit branches'],
            ['name' => 'Delete Branches', 'slug' => 'delete-branches', 'module' => 'Branches', 'description' => 'Can delete branches'],

            // 报表权限
            ['name' => 'View Reports', 'slug' => 'view-reports', 'module' => 'Reports', 'description' => 'Can view all reports'],
            ['name' => 'Export Reports', 'slug' => 'export-reports', 'module' => 'Reports', 'description' => 'Can export reports'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}