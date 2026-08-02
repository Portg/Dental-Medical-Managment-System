<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Role;
use App\Permission;
use App\RolePermission;

class DefaultRolePermissionsSeeder extends Seeder
{
    public function run()
    {
        // 获取角色
        $superAdmin = Role::where('slug', 'super-admin')->first();
        $admin = Role::where('slug', 'admin')->first();
        $doctor = Role::where('slug', 'doctor')->first();
        $nurse = Role::where('slug', 'nurse')->first();
        $receptionist = Role::where('slug', 'receptionist')->first();

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
                'view-patients', 'create-patients', 'edit-patients', 'delete-patients',
                'view-appointments', 'create-appointments', 'edit-appointments', 'delete-appointments',
                'view-invoices', 'create-invoices', 'edit-invoices', 'delete-invoices',
                'view-users', 'create-users', 'edit-users',
                'view-branches', 'create-branches', 'edit-branches',
                'view-chairs', 'create-chairs', 'edit-chairs', 'delete-chairs',
                'view-reports', 'export-reports', 'export-patients', 'view-sensitive-data',
                // view- 必须显式给：MenuService 用 hasPermission() 直查菜单项挂的
                // permission slug，不走 Gate::before，「manage 蕴含 view」在菜单上不生效
                'view-medical-cases', 'manage-medical-cases', 'manage-treatments', 'manage-medical-services',
                'manage-service-categories', 'manage-service-packages', 'import-medical-services',
                'view-surveys', 'manage-surveys',
                'manage-quotations', 'manage-refunds', 'manage-doctor-claims', 'manage-expenses',
                'manage-accounting', 'manage-inventory', 'manage-labs',
                'manage-payroll', 'manage-leave', 'manage-employees', 'manage-holidays',
                'manage-schedules', 'manage-insurance', 'manage-members',
                'manage-patient-settings', 'manage-sms', 'manage-settings',
                'manage-system-maintenance',
                'manage-shifts', 'request-inventory',
                'view-sterilization', 'manage-sterilization',
                // 绩效/工作量报表菜单以此权限为准（见 2026_08_01_000001 迁移）
                'view-own-doctor-report',
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
                'view-invoices',
                'view-medical-cases', 'manage-medical-cases', 'manage-treatments',
                'view-surveys',
                'view-sensitive-data',
                'view-own-doctor-report',
                // 医生制定治疗方案后需出报价单
                'manage-quotations',
                // DoctorScheduleController 据此放行「只看自己的排班」
                'view-own-schedule',
                'request-inventory',
                'view-sterilization', 'manage-sterilization',
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
                'view-appointments',
                // 只读病历。护士录生命体征/护理记录走 VitalSignController /
                // ProgressNoteController 的 edit-patients（上面已有），不需要 manage-。
                // 曾经这里给的是 manage-medical-cases，等于连带放开了建档、改写、删除、
                // 修改审批与 PDF 归档 —— 2026_08_02_140314 已在升级路径上收回，
                // 全新安装必须同步，否则新装环境又是一个越权的护士角色。
                'view-medical-cases',
                'view-surveys',
                'request-inventory',
                'view-sterilization', 'manage-sterilization',
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
                'view-invoices', 'create-invoices',
                'manage-quotations', 'manage-schedules', 'manage-shifts',
                // 前台是办卡/储值/核销优惠券与日常杂费录入的第一线
                'manage-members', 'manage-expenses',
                // 回访问卷的主力：看板 + 生成/批量生成/重置链接（见 2026_08_02_135903）
                'view-surveys', 'manage-surveys',
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
