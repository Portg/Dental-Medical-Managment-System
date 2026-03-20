<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 报表菜单改造：
 * 1. 更新旧 URL 为合并后的新 URL（AG-046：保留旧路由作 301 重定向）
 * 2. 在 Clinical Center 的 Performance 组更新医生报表链接
 * 3. 新增：财务日历、现金汇总、技工单统计、财务明细 菜单项
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. 更新现有菜单项的 URL ──────────────────────────────────────
        $urlMap = [
            'invoice-payments-report'     => 'billing-report',
            'procedure-income-report'     => 'billing-report',
            'patient-source-report'       => 'patient-report',
            'patient-demographics-report' => 'patient-report',
            'doctor-workload-report'      => 'doctor-report',
            'doctor-performance-report'   => 'doctor-report',
        ];

        foreach ($urlMap as $oldUrl => $newUrl) {
            DB::table('menu_items')
                ->where('url', $oldUrl)
                ->update(['url' => $newUrl, 'updated_at' => now()]);
        }

        // ── 2. 更新 Clinical Center 中 Performance 组的链接 ──────────────
        DB::table('menu_items')
            ->where('title_key', 'menu.group_performance')
            ->where('url', 'doctor-performance-report')
            ->update(['url' => 'doctor-report', 'updated_at' => now()]);

        // ── 3. 新增菜单项（幂等：先检查是否已存在）──────────────────────

        // 获取权限 ID
        $viewReportsPermId = DB::table('permissions')
            ->where('slug', 'view-reports')
            ->value('id');

        // 获取 SA+Admin 角色 ID
        $saAdminRoleIds = DB::table('roles')
            ->whereIn('slug', ['super-admin', 'admin'])
            ->pluck('id');

        // 查找收入分析组
        $revGroup = DB::table('menu_items')
            ->where('title_key', 'menu.group_revenue_analysis')
            ->first();

        // 查找业务分析组
        $bizGroup = DB::table('menu_items')
            ->where('title_key', 'menu.group_business_analysis')
            ->first();

        $newItems = [];

        if ($revGroup) {
            $newItems[] = [
                'title_key'    => 'menu.financial_calendar',
                'url'          => 'financial-calendar',
                'parent_id'    => $revGroup->id,
                'sort_order'   => 5,
            ];
            $newItems[] = [
                'title_key'    => 'menu.cash_summary_report',
                'url'          => 'cash-summary-report',
                'parent_id'    => $revGroup->id,
                'sort_order'   => 25,
            ];
            $newItems[] = [
                'title_key'    => 'menu.financial_detail_report',
                'url'          => 'financial-detail-report',
                'parent_id'    => $revGroup->id,
                'sort_order'   => 35,
            ];
        }

        if ($bizGroup) {
            $newItems[] = [
                'title_key'    => 'menu.lab_statistics_report',
                'url'          => 'lab-statistics-report',
                'parent_id'    => $bizGroup->id,
                'sort_order'   => 45,
            ];
        }

        foreach ($newItems as $item) {
            // 幂等检查
            if (DB::table('menu_items')->where('title_key', $item['title_key'])->exists()) {
                continue;
            }

            $menuItemId = DB::table('menu_items')->insertGetId([
                'parent_id'     => $item['parent_id'],
                'title_key'     => $item['title_key'],
                'url'           => $item['url'],
                'icon'          => null,
                'permission_id' => $viewReportsPermId,
                'sort_order'    => $item['sort_order'],
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // 关联角色
            $pivotRows = [];
            foreach ($saAdminRoleIds as $roleId) {
                $pivotRows[] = ['role_id' => $roleId, 'menu_item_id' => $menuItemId];
            }
            if ($pivotRows) {
                DB::table('role_menu_items')->insert($pivotRows);
            }
        }

        Cache::forget('menu_tree:all');
    }

    public function down(): void
    {
        // 删除新增菜单项
        $newKeys = [
            'menu.financial_calendar',
            'menu.cash_summary_report',
            'menu.financial_detail_report',
            'menu.lab_statistics_report',
        ];

        foreach ($newKeys as $key) {
            $item = DB::table('menu_items')->where('title_key', $key)->first();
            if ($item) {
                DB::table('role_menu_items')->where('menu_item_id', $item->id)->delete();
                DB::table('menu_items')->where('id', $item->id)->delete();
            }
        }

        // 恢复旧 URL（逆向 urlMap）
        $restoreMap = [
            'billing-report'  => null, // 多对一，无法精确恢复，保留新 URL
            'patient-report'  => null,
            'doctor-report'   => null,
        ];
        // down() 不还原 URL，因为旧路由已设 301 重定向，不影响访问

        Cache::forget('menu_tree:all');
    }
};
