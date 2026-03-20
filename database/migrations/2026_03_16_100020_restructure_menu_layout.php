<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 菜单结构大重组：
 *
 * P0 — 清理重复/异常菜单项
 * P1 — 运营中心拆分为 财务中心 / 库房管理 / 人事行政
 *       数据中心改名报表中心，费用管理迁入财务中心
 *       技工单迁入诊疗中心，椅位迁入诊疗配置
 * P2 — 新增库管角色 + 护士补权限
 */
return new class extends Migration
{
    public function up(): void
    {
        // =====================================================================
        // P0: 清理重复 / 异常菜单项
        // =====================================================================

        // 删除 id=91 (menu.system_users → /users)，与 id=92 (menu.users → /users) 重复
        $dup91 = DB::table('menu_items')->where('title_key', 'menu.system_users')->where('url', 'users')->first();
        if ($dup91) {
            DB::table('role_menu_items')->where('menu_item_id', $dup91->id)->delete();
            DB::table('menu_items')->where('id', $dup91->id)->delete();
        }

        // 清理 group 节点多余的 url（有子节点时 url 被 Blade 忽略，但造成数据混乱）
        $groupsWithUrl = [
            'menu.group_member_management',     // id=7,  url=members (子 id=8 也指向 members)
            'menu.group_medical_records',        // id=23, url=medical-cases (子 id=24 也指向)
            'menu.group_insurance_claims',       // id=38, url=insurance-companies
            'menu.group_accounts_management',    // id=42, url=self-accounts
            'menu.group_performance',            // id=61, url=doctor-report
        ];
        DB::table('menu_items')
            ->whereIn('title_key', $groupsWithUrl)
            ->update(['url' => null]);

        // =====================================================================
        // P1: 创建 3 个新的一级分组
        // =====================================================================

        $now = now();

        $financialCenterId = DB::table('menu_items')->insertGetId([
            'parent_id'     => null,
            'title_key'     => 'menu.financial_center',
            'url'           => null,
            'icon'          => 'icon-wallet',
            'permission_id' => null,
            'sort_order'    => 40,
            'is_active'     => true,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        $warehouseId = DB::table('menu_items')->insertGetId([
            'parent_id'     => null,
            'title_key'     => 'menu.warehouse_management',
            'url'           => null,
            'icon'          => 'icon-layers',
            'permission_id' => null,
            'sort_order'    => 50,
            'is_active'     => true,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        $hrAdminId = DB::table('menu_items')->insertGetId([
            'parent_id'     => null,
            'title_key'     => 'menu.hr_admin',
            'url'           => null,
            'icon'          => 'icon-users',
            'permission_id' => null,
            'sort_order'    => 60,
            'is_active'     => true,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        // 为新根节点创建 role_menu_items（所有 5 个角色）
        $allRoleIds = DB::table('roles')->pluck('id');
        foreach ([$financialCenterId, $warehouseId, $hrAdminId] as $rootId) {
            foreach ($allRoleIds as $roleId) {
                DB::table('role_menu_items')->insertOrIgnore([
                    'role_id'      => $roleId,
                    'menu_item_id' => $rootId,
                ]);
            }
        }

        // =====================================================================
        // P1: 数据中心 → 报表中心（改名 + 调整排序）
        // =====================================================================

        DB::table('menu_items')
            ->where('title_key', 'menu.data_center')
            ->update(['title_key' => 'menu.report_center', 'sort_order' => 70]);

        // 系统设置排序后移
        DB::table('menu_items')
            ->where('title_key', 'menu.system_settings')
            ->update(['sort_order' => 80]);

        // =====================================================================
        // P1: 收费/保险/账务/费用 → 财务中心
        // =====================================================================

        // 收费管理 group
        DB::table('menu_items')
            ->where('title_key', 'menu.group_billing')
            ->update(['parent_id' => $financialCenterId, 'sort_order' => 10]);

        // 保险理赔 group
        DB::table('menu_items')
            ->where('title_key', 'menu.group_insurance_claims')
            ->update(['parent_id' => $financialCenterId, 'sort_order' => 20]);

        // 账务管理 group
        DB::table('menu_items')
            ->where('title_key', 'menu.group_accounts_management')
            ->update(['parent_id' => $financialCenterId, 'sort_order' => 30]);

        // 费用分析 → 费用管理，改标题 + 迁入财务中心
        DB::table('menu_items')
            ->where('title_key', 'menu.group_expense_analysis')
            ->update([
                'parent_id'  => $financialCenterId,
                'title_key'  => 'menu.group_expense_management',
                'sort_order' => 40,
            ]);

        // =====================================================================
        // P1: 耗材/供应商 → 库房管理（扁平化，去掉 group_consumables 中间层）
        // =====================================================================

        $consumablesGroup = DB::table('menu_items')
            ->where('title_key', 'menu.group_consumables')
            ->first();

        if ($consumablesGroup) {
            // 将 group_consumables 的所有子项直接挂到 warehouse_management 下
            // 并重新排序
            $sortMap = [
                'inventory.inventory_query'        => 10,  // 库存查询
                'inventory.stock_in'               => 20,  // 入库管理
                'inventory.stock_out'              => 30,  // 出库管理
                'menu.requisition_management'       => 40,  // 申领管理
                'menu.inventory_check_management'   => 50,  // 盘点管理
                'inventory.service_consumables'     => 70,  // 项目耗材设置
                'inventory.categories'             => 80,  // 物品分类
                'inventory.items'                  => 90,  // 物品目录
                'inventory.bulk_import'            => 100, // 批量导入
            ];

            foreach ($sortMap as $titleKey => $sort) {
                DB::table('menu_items')
                    ->where('title_key', $titleKey)
                    ->update(['parent_id' => $warehouseId, 'sort_order' => $sort]);
            }

            // 停用 group_consumables（子项已全部迁移）
            DB::table('role_menu_items')->where('menu_item_id', $consumablesGroup->id)->delete();
            DB::table('menu_items')->where('id', $consumablesGroup->id)->update(['is_active' => false]);
        }

        // 供应商管理 → 库房管理（扁平叶子，清除图标以与同级对齐）
        DB::table('menu_items')
            ->where('title_key', 'menu.group_supplier')
            ->update(['parent_id' => $warehouseId, 'sort_order' => 60, 'icon' => null]);

        // =====================================================================
        // P1: HR 相关 → 人事行政
        // =====================================================================

        // 员工管理 group
        DB::table('menu_items')
            ->where('title_key', 'menu.group_employee')
            ->update(['parent_id' => $hrAdminId, 'sort_order' => 10]);

        // 个人工资单 → 员工管理 group 下
        $employeeGroup = DB::table('menu_items')->where('title_key', 'menu.group_employee')->first();
        if ($employeeGroup) {
            DB::table('menu_items')
                ->where('title_key', 'menu.individual_payslip')
                ->update(['parent_id' => $employeeGroup->id, 'sort_order' => 35, 'icon' => null]);
        }

        // 绩效管理 group
        DB::table('menu_items')
            ->where('title_key', 'menu.group_performance')
            ->update(['parent_id' => $hrAdminId, 'sort_order' => 20]);

        // 考勤假期 group
        DB::table('menu_items')
            ->where('title_key', 'menu.group_attendance_leave')
            ->update(['parent_id' => $hrAdminId, 'sort_order' => 30]);

        // =====================================================================
        // P1: 技工单管理 → 诊疗中心
        // =====================================================================

        $clinicalCenter = DB::table('menu_items')->where('title_key', 'menu.clinical_center')->first();
        if ($clinicalCenter) {
            DB::table('menu_items')
                ->where('title_key', 'menu.group_lab_management')
                ->update(['parent_id' => $clinicalCenter->id, 'sort_order' => 25]);
        }

        // =====================================================================
        // P1: 椅位管理 → 诊疗配置 group
        // =====================================================================

        $clinicalConfig = DB::table('menu_items')->where('title_key', 'menu.group_clinical_config')->first();
        if ($clinicalConfig) {
            DB::table('menu_items')
                ->where('title_key', 'menu.chairs')
                ->update(['parent_id' => $clinicalConfig->id, 'sort_order' => 5]);
        }

        // =====================================================================
        // P1: 停用运营中心（子项已全部迁走）
        // =====================================================================

        $opsCenter = DB::table('menu_items')->where('title_key', 'menu.operations_center')->first();
        if ($opsCenter) {
            $hasChildren = DB::table('menu_items')
                ->where('parent_id', $opsCenter->id)
                ->where('is_active', true)
                ->exists();

            if (!$hasChildren) {
                DB::table('menu_items')->where('id', $opsCenter->id)->update(['is_active' => false]);
            }
        }

        // =====================================================================
        // P2: 新增库管角色
        // =====================================================================

        $existingRole = DB::table('roles')->where('slug', 'inventory-manager')->first();
        if (!$existingRole) {
            $inventoryRoleId = DB::table('roles')->insertGetId([
                'name'       => '库管',
                'slug'       => 'inventory-manager',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // 授权：manage-inventory + request-inventory
            $invPerms = DB::table('permissions')
                ->whereIn('slug', ['manage-inventory', 'request-inventory'])
                ->pluck('id');

            foreach ($invPerms as $permId) {
                DB::table('role_permissions')->insertOrIgnore([
                    'role_id'       => $inventoryRoleId,
                    'permission_id' => $permId,
                ]);
            }

            // 库管角色的 role_menu_items：今日工作 + 库房管理 + 库房下所有子项
            $todayWork = DB::table('menu_items')->where('title_key', 'menu.today_work')->first();
            $menuIdsForInvRole = DB::table('menu_items')
                ->where('parent_id', $warehouseId)
                ->pluck('id')
                ->toArray();

            if ($todayWork) {
                $menuIdsForInvRole[] = $todayWork->id;
            }
            $menuIdsForInvRole[] = $warehouseId;

            foreach ($menuIdsForInvRole as $mid) {
                DB::table('role_menu_items')->insertOrIgnore([
                    'role_id'      => $inventoryRoleId,
                    'menu_item_id' => $mid,
                ]);
            }
        }

        // =====================================================================
        // P2: 护士补 request-inventory 权限
        // =====================================================================

        $nurseRole = DB::table('roles')->where('slug', 'nurse')->first();
        $reqInvPerm = DB::table('permissions')->where('slug', 'request-inventory')->first();

        if ($nurseRole && $reqInvPerm) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id'       => $nurseRole->id,
                'permission_id' => $reqInvPerm->id,
            ]);
        }

        // =====================================================================
        // 清除菜单缓存
        // =====================================================================
        Cache::forget('menu_tree:all');
    }

    public function down(): void
    {
        // 恢复操作较复杂，仅做关键回滚

        $opsCenter = DB::table('menu_items')->where('title_key', 'menu.operations_center')->first();
        if ($opsCenter) {
            DB::table('menu_items')->where('id', $opsCenter->id)->update(['is_active' => true]);
        }

        // 报表中心 → 数据中心
        DB::table('menu_items')
            ->where('title_key', 'menu.report_center')
            ->update(['title_key' => 'menu.data_center', 'sort_order' => 50]);

        // 系统设置恢复排序
        DB::table('menu_items')
            ->where('title_key', 'menu.system_settings')
            ->update(['sort_order' => 60]);

        // 删除新建的根节点
        $newRoots = DB::table('menu_items')
            ->whereIn('title_key', ['menu.financial_center', 'menu.warehouse_management', 'menu.hr_admin'])
            ->pluck('id');

        if ($newRoots->isNotEmpty()) {
            // 恢复子项到运营中心
            if ($opsCenter) {
                DB::table('menu_items')
                    ->whereIn('parent_id', $newRoots)
                    ->update(['parent_id' => $opsCenter->id]);
            }

            DB::table('role_menu_items')->whereIn('menu_item_id', $newRoots)->delete();
            DB::table('menu_items')->whereIn('id', $newRoots)->delete();
        }

        // 恢复 group_consumables
        DB::table('menu_items')
            ->where('title_key', 'menu.group_consumables')
            ->update(['is_active' => true]);

        // 删除库管角色
        $invRole = DB::table('roles')->where('slug', 'inventory-manager')->first();
        if ($invRole) {
            DB::table('role_permissions')->where('role_id', $invRole->id)->delete();
            DB::table('role_menu_items')->where('role_id', $invRole->id)->delete();
            DB::table('roles')->where('id', $invRole->id)->delete();
        }

        // 撤销护士的 request-inventory 权限
        $nurseRole = DB::table('roles')->where('slug', 'nurse')->first();
        $reqInvPerm = DB::table('permissions')->where('slug', 'request-inventory')->first();
        if ($nurseRole && $reqInvPerm) {
            DB::table('role_permissions')
                ->where('role_id', $nurseRole->id)
                ->where('permission_id', $reqInvPerm->id)
                ->delete();
        }

        Cache::forget('menu_tree:all');
    }
};
