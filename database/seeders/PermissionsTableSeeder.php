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

            // 诊椅管理权限
            ['name' => 'View Chairs', 'slug' => 'view-chairs', 'module' => 'Chairs', 'description' => 'Can view chairs'],
            ['name' => 'Create Chairs', 'slug' => 'create-chairs', 'module' => 'Chairs', 'description' => 'Can create chairs'],
            ['name' => 'Edit Chairs', 'slug' => 'edit-chairs', 'module' => 'Chairs', 'description' => 'Can edit chairs'],
            ['name' => 'Delete Chairs', 'slug' => 'delete-chairs', 'module' => 'Chairs', 'description' => 'Can delete chairs'],

            // 报表权限
            ['name' => 'View Reports', 'slug' => 'view-reports', 'module' => 'Reports', 'description' => 'Can view all reports'],
            ['name' => 'Export Reports', 'slug' => 'export-reports', 'module' => 'Reports', 'description' => 'Can export reports'],

            // 病历管理权限
            ['name' => 'Manage Medical Cases', 'slug' => 'manage-medical-cases', 'module' => 'Medical', 'description' => 'Can manage medical cases'],
            ['name' => 'Manage Treatments', 'slug' => 'manage-treatments', 'module' => 'Medical', 'description' => 'Can manage treatments and prescriptions'],
            ['name' => 'Manage Medical Services', 'slug' => 'manage-medical-services', 'module' => 'Medical', 'description' => 'Can manage medical services and templates'],

            // 财务管理权限
            ['name' => 'Manage Quotations', 'slug' => 'manage-quotations', 'module' => 'Finance', 'description' => 'Can manage quotations'],
            ['name' => 'Manage Refunds', 'slug' => 'manage-refunds', 'module' => 'Finance', 'description' => 'Can manage refunds'],
            ['name' => 'Manage Doctor Claims', 'slug' => 'manage-doctor-claims', 'module' => 'Finance', 'description' => 'Can manage doctor claims and commissions'],
            ['name' => 'Manage Expenses', 'slug' => 'manage-expenses', 'module' => 'Finance', 'description' => 'Can manage expenses'],
            ['name' => 'Manage Accounting', 'slug' => 'manage-accounting', 'module' => 'Finance', 'description' => 'Can manage chart of accounts'],

            // 库存管理权限
            ['name' => 'Manage Inventory', 'slug' => 'manage-inventory', 'module' => 'Inventory', 'description' => 'Can manage inventory and stock'],
            ['name' => 'Manage Labs', 'slug' => 'manage-labs', 'module' => 'Inventory', 'description' => 'Can manage labs and lab cases'],

            // 人事管理权限
            ['name' => 'Manage Payroll', 'slug' => 'manage-payroll', 'module' => 'HR', 'description' => 'Can manage payroll and salaries'],
            ['name' => 'Manage Leave', 'slug' => 'manage-leave', 'module' => 'HR', 'description' => 'Can manage leave requests'],
            ['name' => 'Manage Employees', 'slug' => 'manage-employees', 'module' => 'HR', 'description' => 'Can manage employee contracts'],
            ['name' => 'Manage Holidays', 'slug' => 'manage-holidays', 'module' => 'HR', 'description' => 'Can manage holidays'],
            ['name' => 'Manage Schedules', 'slug' => 'manage-schedules', 'module' => 'HR', 'description' => 'Can manage doctor schedules'],

            // 系统设置权限
            ['name' => 'Manage Insurance', 'slug' => 'manage-insurance', 'module' => 'Settings', 'description' => 'Can manage insurance companies'],
            ['name' => 'Manage Members', 'slug' => 'manage-members', 'module' => 'Settings', 'description' => 'Can manage membership system'],
            ['name' => 'Manage Patient Settings', 'slug' => 'manage-patient-settings', 'module' => 'Settings', 'description' => 'Can manage patient tags and sources'],
            ['name' => 'Manage SMS', 'slug' => 'manage-sms', 'module' => 'Settings', 'description' => 'Can manage SMS settings and logs'],
            ['name' => 'Manage System Settings', 'slug' => 'manage-settings', 'module' => 'Settings', 'description' => 'Can manage system settings'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}