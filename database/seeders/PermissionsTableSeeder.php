<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Permission;

class PermissionsTableSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // 患者管理
            ['name' => '查看患者', 'slug' => 'view-patients', 'module' => '患者管理', 'description' => '查看患者列表及详情'],
            ['name' => '新增患者', 'slug' => 'create-patients', 'module' => '患者管理', 'description' => '新建患者档案'],
            ['name' => '编辑患者', 'slug' => 'edit-patients', 'module' => '患者管理', 'description' => '修改患者信息'],
            ['name' => '删除患者', 'slug' => 'delete-patients', 'module' => '患者管理', 'description' => '删除患者档案'],

            // 预约管理
            ['name' => '查看预约', 'slug' => 'view-appointments', 'module' => '预约管理', 'description' => '查看预约列表及日历'],
            ['name' => '新增预约', 'slug' => 'create-appointments', 'module' => '预约管理', 'description' => '创建新预约'],
            ['name' => '编辑预约', 'slug' => 'edit-appointments', 'module' => '预约管理', 'description' => '修改预约信息'],
            ['name' => '删除预约', 'slug' => 'delete-appointments', 'module' => '预约管理', 'description' => '取消或删除预约'],

            // 账单管理
            ['name' => '查看账单', 'slug' => 'view-invoices', 'module' => '账单管理', 'description' => '查看账单列表及详情'],
            ['name' => '新增账单', 'slug' => 'create-invoices', 'module' => '账单管理', 'description' => '创建新账单'],
            ['name' => '编辑账单', 'slug' => 'edit-invoices', 'module' => '账单管理', 'description' => '修改账单信息'],
            ['name' => '删除账单', 'slug' => 'delete-invoices', 'module' => '账单管理', 'description' => '删除账单记录'],

            // 用户管理
            ['name' => '查看用户', 'slug' => 'view-users', 'module' => '用户管理', 'description' => '查看系统用户列表'],
            ['name' => '新增用户', 'slug' => 'create-users', 'module' => '用户管理', 'description' => '创建系统用户'],
            ['name' => '编辑用户', 'slug' => 'edit-users', 'module' => '用户管理', 'description' => '修改用户信息'],
            ['name' => '删除用户', 'slug' => 'delete-users', 'module' => '用户管理', 'description' => '删除系统用户'],

            // 机构管理
            ['name' => '查看分院', 'slug' => 'view-branches', 'module' => '机构管理', 'description' => '查看分院列表'],
            ['name' => '新增分院', 'slug' => 'create-branches', 'module' => '机构管理', 'description' => '创建新分院'],
            ['name' => '编辑分院', 'slug' => 'edit-branches', 'module' => '机构管理', 'description' => '修改分院信息'],
            ['name' => '删除分院', 'slug' => 'delete-branches', 'module' => '机构管理', 'description' => '删除分院'],

            // 诊椅管理
            ['name' => '查看诊椅', 'slug' => 'view-chairs', 'module' => '诊椅管理', 'description' => '查看诊椅列表'],
            ['name' => '新增诊椅', 'slug' => 'create-chairs', 'module' => '诊椅管理', 'description' => '添加新诊椅'],
            ['name' => '编辑诊椅', 'slug' => 'edit-chairs', 'module' => '诊椅管理', 'description' => '修改诊椅信息'],
            ['name' => '删除诊椅', 'slug' => 'delete-chairs', 'module' => '诊椅管理', 'description' => '删除诊椅'],

            // 报表管理
            ['name' => '查看报表', 'slug' => 'view-reports', 'module' => '报表管理', 'description' => '查看各类统计报表'],
            ['name' => '导出报表', 'slug' => 'export-reports', 'module' => '报表管理', 'description' => '导出报表数据'],

            // 医疗管理
            ['name' => '管理病例', 'slug' => 'manage-medical-cases', 'module' => '医疗管理', 'description' => '管理病例记录'],
            ['name' => '管理治疗', 'slug' => 'manage-treatments', 'module' => '医疗管理', 'description' => '管理治疗方案与处方'],
            ['name' => '管理医疗服务', 'slug' => 'manage-medical-services', 'module' => '医疗管理', 'description' => '管理医疗服务项目与模板'],

            // 财务管理
            ['name' => '管理报价单', 'slug' => 'manage-quotations', 'module' => '财务管理', 'description' => '管理报价单'],
            ['name' => '管理退款', 'slug' => 'manage-refunds', 'module' => '财务管理', 'description' => '管理退款记录'],
            ['name' => '管理医生提成', 'slug' => 'manage-doctor-claims', 'module' => '财务管理', 'description' => '管理医生提成与佣金'],
            ['name' => '管理费用', 'slug' => 'manage-expenses', 'module' => '财务管理', 'description' => '管理支出费用'],
            ['name' => '管理会计', 'slug' => 'manage-accounting', 'module' => '财务管理', 'description' => '管理会计科目表'],

            // 库存管理
            ['name' => '管理库存', 'slug' => 'manage-inventory', 'module' => '库存管理', 'description' => '管理库存与出入库'],
            ['name' => '管理技工所', 'slug' => 'manage-labs', 'module' => '库存管理', 'description' => '管理技工所与加工单'],

            // 人事管理
            ['name' => '管理薪资', 'slug' => 'manage-payroll', 'module' => '人事管理', 'description' => '管理工资与薪酬发放'],
            ['name' => '管理请假', 'slug' => 'manage-leave', 'module' => '人事管理', 'description' => '管理请假申请与审批'],
            ['name' => '管理员工', 'slug' => 'manage-employees', 'module' => '人事管理', 'description' => '管理员工合同与档案'],
            ['name' => '管理假期', 'slug' => 'manage-holidays', 'module' => '人事管理', 'description' => '管理法定假期设置'],
            ['name' => '管理排班', 'slug' => 'manage-schedules', 'module' => '人事管理', 'description' => '管理医生排班表'],

            // 系统设置
            ['name' => '管理角色', 'slug' => 'manage-roles', 'module' => '系统设置', 'description' => '管理系统角色'],
            ['name' => '管理权限', 'slug' => 'manage-permissions', 'module' => '系统设置', 'description' => '管理权限定义'],
            ['name' => '管理角色权限', 'slug' => 'manage-role-permissions', 'module' => '系统设置', 'description' => '分配角色权限'],
            ['name' => '管理保险', 'slug' => 'manage-insurance', 'module' => '系统设置', 'description' => '管理保险公司与保险方案'],
            ['name' => '管理会员', 'slug' => 'manage-members', 'module' => '系统设置', 'description' => '管理会员体系'],
            ['name' => '管理患者设置', 'slug' => 'manage-patient-settings', 'module' => '系统设置', 'description' => '管理患者标签与来源'],
            ['name' => '管理短信', 'slug' => 'manage-sms', 'module' => '系统设置', 'description' => '管理短信设置与发送记录'],
            ['name' => '管理系统设置', 'slug' => 'manage-settings', 'module' => '系统设置', 'description' => '管理系统全局设置'],
            ['name' => '管理系统维护', 'slug' => 'manage-system-maintenance', 'module' => '系统设置', 'description' => '访问系统维护页面（备份、清理、日志）'],
            ['name' => '管理菜单', 'slug' => 'manage-menu-items', 'module' => '系统设置', 'description' => '管理菜单项与菜单结构'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}