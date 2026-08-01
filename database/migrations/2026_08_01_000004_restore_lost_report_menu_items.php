<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 恢复被 MenuItemsSeeder 抹掉的报表类菜单入口，并还原绩效菜单的双入口设计。
 *
 * 这六项由 2026_03_16_100003 / 100007 / 100023 迁移创建，随后被 seeder 的
 * truncate 抹掉。六条路由与控制器一直健在，仅仅是侧边栏没有入口。
 *
 * 其中 menu.my_performance 尤为关键：它是医生查看本人绩效的专属入口。
 * 因其缺失，2026_08_01_000001 迁移曾把「运营中心 › 绩效管理」一系菜单的权限
 * 从 view-reports 改成 view-own-doctor-report，好让医生能看到绩效报表 ——
 * 那是针对被删菜单的权宜之计。此处恢复原设计：
 *   - 医生 → 诊疗中心 › 我的绩效（view-own-doctor-report，数据按本人限定）
 *   - 管理员 → 运营中心 › 绩效管理（view-reports，全院视角）
 * 两个入口以权限区分，避免医生同时看到两条通往同一页面的路径。
 */
return new class extends Migration
{
    /** 需恢复的菜单项：父级 title_key => 菜单定义 */
    private const ITEMS = [
        // 2026_03_16_100003：收入分析组
        ['parent' => 'menu.group_revenue_analysis',  'title_key' => 'menu.financial_calendar',      'url' => 'financial-calendar',      'perm' => 'view-reports', 'sort' => 5],
        ['parent' => 'menu.group_revenue_analysis',  'title_key' => 'menu.cash_summary_report',     'url' => 'cash-summary-report',     'perm' => 'view-reports', 'sort' => 25],
        ['parent' => 'menu.group_revenue_analysis',  'title_key' => 'menu.financial_detail_report', 'url' => 'financial-detail-report', 'perm' => 'view-reports', 'sort' => 35],
        // 2026_03_16_100007
        ['parent' => 'menu.group_revenue_analysis',  'title_key' => 'menu.unpaid_invoices_report',  'url' => 'unpaid-invoices',         'perm' => 'view-reports', 'sort' => 40],
        // 2026_03_16_100003：业务分析组
        ['parent' => 'menu.group_business_analysis', 'title_key' => 'menu.lab_statistics_report',   'url' => 'lab-statistics-report',   'perm' => 'view-reports', 'sort' => 45],
        // 2026_03_16_100023：医生本人绩效入口
        ['parent' => 'menu.clinical_center',         'title_key' => 'menu.my_performance',          'url' => 'doctor-report',           'perm' => 'view-own-doctor-report', 'sort' => 90, 'icon' => 'icon-graph'],
    ];

    /** 恢复 my_performance 后，这些菜单还原为管理员视角的 view-reports */
    private const REVERT_TO_VIEW_REPORTS = [
        'menu.group_performance',
        'menu.doctor_performance_report',
        'menu.doctor_workload_report',
    ];

    public function up(): void
    {
        $permIds = DB::table('permissions')->pluck('id', 'slug');
        $now     = now();

        foreach (self::ITEMS as $item) {
            if (DB::table('menu_items')->where('title_key', $item['title_key'])->exists()) {
                continue;
            }

            $parentId = DB::table('menu_items')->where('title_key', $item['parent'])->value('id');
            $permId   = $permIds[$item['perm']] ?? null;

            // 父级不存在（菜单结构已重组）或权限缺失时跳过：
            // 宁可少一个入口，也不要挂到错误的父级、或写出 permission_id = null
            // 而变成全员可见。
            if ($parentId === null || $permId === null) {
                continue;
            }

            DB::table('menu_items')->insert([
                'parent_id'     => $parentId,
                'title_key'     => $item['title_key'],
                'url'           => $item['url'],
                'icon'          => $item['icon'] ?? null,
                'permission_id' => $permId,
                'sort_order'    => $item['sort'],
                'is_active'     => true,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        // 仅当医生确实拿回了「我的绩效」入口，才把管理员侧菜单收回 view-reports，
        // 否则医生会既看不到我的绩效、也看不到绩效报表。
        $hasMyPerformance = DB::table('menu_items')
            ->where('title_key', 'menu.my_performance')
            ->where('is_active', true)
            ->exists();

        $viewReportsId = $permIds['view-reports'] ?? null;

        if ($hasMyPerformance && $viewReportsId !== null) {
            DB::table('menu_items')
                ->whereIn('title_key', self::REVERT_TO_VIEW_REPORTS)
                ->update(['permission_id' => $viewReportsId, 'updated_at' => $now]);
        }

        Cache::forget('menu_tree:all');
    }

    public function down(): void
    {
        // 菜单项恢复不做逆向删除：up() 为「缺则补」的条件插入，无法区分某项是
        // 本迁移补回的还是该库本来就有的，按 title_key 删除会误伤。
        // 权限指向则可安全还原为 2026_08_01_000001 迁移设定的状态。
        $ownReportId = DB::table('permissions')->where('slug', 'view-own-doctor-report')->value('id');

        if ($ownReportId !== null) {
            DB::table('menu_items')
                ->whereIn('title_key', self::REVERT_TO_VIEW_REPORTS)
                ->update(['permission_id' => $ownReportId, 'updated_at' => now()]);
        }

        Cache::forget('menu_tree:all');
    }
};
