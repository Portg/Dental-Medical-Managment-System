<?php

use Illuminate\Database\Seeder;
use App\Permission;
use App\Role;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            // User Management
            ['name' => 'View Users', 'slug' => 'view-users', 'description' => 'Can view users list'],
            ['name' => 'Create Users', 'slug' => 'create-users', 'description' => 'Can create new users'],
            ['name' => 'Edit Users', 'slug' => 'edit-users', 'description' => 'Can edit existing users'],
            ['name' => 'Delete Users', 'slug' => 'delete-users', 'description' => 'Can delete users'],

            // Role Management
            ['name' => 'View Roles', 'slug' => 'view-roles', 'description' => 'Can view roles list'],
            ['name' => 'Create Roles', 'slug' => 'create-roles', 'description' => 'Can create new roles'],
            ['name' => 'Edit Roles', 'slug' => 'edit-roles', 'description' => 'Can edit existing roles'],
            ['name' => 'Delete Roles', 'slug' => 'delete-roles', 'description' => 'Can delete roles'],

            // Permission Management
            ['name' => 'View Permissions', 'slug' => 'view-permissions', 'description' => 'Can view permissions list'],
            ['name' => 'Create Permissions', 'slug' => 'create-permissions', 'description' => 'Can create new permissions'],
            ['name' => 'Edit Permissions', 'slug' => 'edit-permissions', 'description' => 'Can edit existing permissions'],
            ['name' => 'Delete Permissions', 'slug' => 'delete-permissions', 'description' => 'Can delete permissions'],
            ['name' => 'Manage Role Permissions', 'slug' => 'manage-role-permissions', 'description' => 'Can assign/revoke permissions to roles'],

            // Patient Management
            ['name' => 'View Patients', 'slug' => 'view-patients', 'description' => 'Can view patients list'],
            ['name' => 'Create Patients', 'slug' => 'create-patients', 'description' => 'Can register new patients'],
            ['name' => 'Edit Patients', 'slug' => 'edit-patients', 'description' => 'Can edit patient information'],
            ['name' => 'Delete Patients', 'slug' => 'delete-patients', 'description' => 'Can delete patients'],

            // Appointment Management
            ['name' => 'View Appointments', 'slug' => 'view-appointments', 'description' => 'Can view appointments'],
            ['name' => 'Create Appointments', 'slug' => 'create-appointments', 'description' => 'Can create new appointments'],
            ['name' => 'Edit Appointments', 'slug' => 'edit-appointments', 'description' => 'Can edit appointments'],
            ['name' => 'Delete Appointments', 'slug' => 'delete-appointments', 'description' => 'Can delete appointments'],

            // Medical Records
            ['name' => 'View Medical Records', 'slug' => 'view-medical-records', 'description' => 'Can view patient medical records'],
            ['name' => 'Create Medical Records', 'slug' => 'create-medical-records', 'description' => 'Can create medical records'],
            ['name' => 'Edit Medical Records', 'slug' => 'edit-medical-records', 'description' => 'Can edit medical records'],
            ['name' => 'Delete Medical Records', 'slug' => 'delete-medical-records', 'description' => 'Can delete medical records'],

            // Invoice Management
            ['name' => 'View Invoices', 'slug' => 'view-invoices', 'description' => 'Can view invoices'],
            ['name' => 'Create Invoices', 'slug' => 'create-invoices', 'description' => 'Can create new invoices'],
            ['name' => 'Edit Invoices', 'slug' => 'edit-invoices', 'description' => 'Can edit invoices'],
            ['name' => 'Delete Invoices', 'slug' => 'delete-invoices', 'description' => 'Can delete invoices'],

            // Payment Management
            ['name' => 'View Payments', 'slug' => 'view-payments', 'description' => 'Can view payments'],
            ['name' => 'Create Payments', 'slug' => 'create-payments', 'description' => 'Can record payments'],
            ['name' => 'Edit Payments', 'slug' => 'edit-payments', 'description' => 'Can edit payments'],
            ['name' => 'Delete Payments', 'slug' => 'delete-payments', 'description' => 'Can delete payments'],

            // Expense Management
            ['name' => 'View Expenses', 'slug' => 'view-expenses', 'description' => 'Can view expenses'],
            ['name' => 'Create Expenses', 'slug' => 'create-expenses', 'description' => 'Can create new expenses'],
            ['name' => 'Edit Expenses', 'slug' => 'edit-expenses', 'description' => 'Can edit expenses'],
            ['name' => 'Delete Expenses', 'slug' => 'delete-expenses', 'description' => 'Can delete expenses'],

            // Reports
            ['name' => 'View Reports', 'slug' => 'view-reports', 'description' => 'Can view system reports'],
            ['name' => 'Generate Reports', 'slug' => 'generate-reports', 'description' => 'Can generate custom reports'],

            // Branch Management
            ['name' => 'View Branches', 'slug' => 'view-branches', 'description' => 'Can view branches'],
            ['name' => 'Create Branches', 'slug' => 'create-branches', 'description' => 'Can create new branches'],
            ['name' => 'Edit Branches', 'slug' => 'edit-branches', 'description' => 'Can edit branches'],
            ['name' => 'Delete Branches', 'slug' => 'delete-branches', 'description' => 'Can delete branches'],

            // Settings
            ['name' => 'View Settings', 'slug' => 'view-settings', 'description' => 'Can view system settings'],
            ['name' => 'Edit Settings', 'slug' => 'edit-settings', 'description' => 'Can modify system settings'],

            // Dashboard Access
            ['name' => 'Access Super Admin Dashboard', 'slug' => 'access-super-admin-dashboard', 'description' => 'Can access super admin dashboard'],
            ['name' => 'Access Admin Dashboard', 'slug' => 'access-admin-dashboard', 'description' => 'Can access admin dashboard'],
            ['name' => 'Access Doctor Dashboard', 'slug' => 'access-doctor-dashboard', 'description' => 'Can access doctor dashboard'],
            ['name' => 'Access Receptionist Dashboard', 'slug' => 'access-receptionist-dashboard', 'description' => 'Can access receptionist dashboard'],
            ['name' => 'Access Nurse Dashboard', 'slug' => 'access-nurse-dashboard', 'description' => 'Can access nurse dashboard'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        $this->assignPermissionsToRoles();
    }

    /**
     * Assign permissions to roles based on their responsibilities
     */
    private function assignPermissionsToRoles()
    {
        $superAdmin = Role::where('name', 'Super Administrator')->first();
        $admin = Role::where('name', 'Administrator')->first();
        $doctor = Role::where('name', 'Doctor')->first();
        $receptionist = Role::where('name', 'Receptionist')->first();
        $nurse = Role::where('name', 'Nurse')->first();

        if ($superAdmin) {
            $allPermissions = Permission::all();
            $superAdmin->permissions()->sync($allPermissions->pluck('id'));
        }

        if ($admin) {
            $adminPermissions = Permission::whereIn('slug', [
                'view-users', 'create-users', 'edit-users',
                'view-patients', 'create-patients', 'edit-patients', 'delete-patients',
                'view-appointments', 'create-appointments', 'edit-appointments', 'delete-appointments',
                'view-invoices', 'create-invoices', 'edit-invoices', 'delete-invoices',
                'view-payments', 'create-payments', 'edit-payments',
                'view-expenses', 'create-expenses', 'edit-expenses', 'delete-expenses',
                'view-reports', 'generate-reports',
                'view-branches', 'create-branches', 'edit-branches',
                'view-settings', 'edit-settings',
                'access-admin-dashboard',
            ])->pluck('id');
            $admin->permissions()->sync($adminPermissions);
        }

        if ($doctor) {
            $doctorPermissions = Permission::whereIn('slug', [
                'view-patients', 'edit-patients',
                'view-appointments', 'edit-appointments',
                'view-medical-records', 'create-medical-records', 'edit-medical-records',
                'view-invoices', 'create-invoices',
                'access-doctor-dashboard',
            ])->pluck('id');
            $doctor->permissions()->sync($doctorPermissions);
        }

        if ($receptionist) {
            $receptionistPermissions = Permission::whereIn('slug', [
                'view-patients', 'create-patients', 'edit-patients',
                'view-appointments', 'create-appointments', 'edit-appointments',
                'view-invoices', 'create-invoices', 'edit-invoices',
                'view-payments', 'create-payments',
                'access-receptionist-dashboard',
            ])->pluck('id');
            $receptionist->permissions()->sync($receptionistPermissions);
        }

        if ($nurse) {
            $nursePermissions = Permission::whereIn('slug', [
                'view-patients', 'edit-patients',
                'view-appointments',
                'view-medical-records', 'create-medical-records',
                'access-nurse-dashboard',
            ])->pluck('id');
            $nurse->permissions()->sync($nursePermissions);
        }
    }
}
