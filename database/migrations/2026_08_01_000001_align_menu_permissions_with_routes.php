<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 对齐菜单权限与路由/控制器实际要求的权限。
 *
 * 背景：侧边栏可见性由 menu_items.permission_id 决定，页面能否打开由控制器
 * 构造函数的 can: 中间件决定。两者长期各自维护，导致「菜单看得见、点进去 403」
 * （医生 7 项、护士 6 项、前台 5 项、库管 4 项），以及反向的「有权限却看不到菜单」。
 *
 * 本迁移只处理**应当收紧菜单**的一侧；应当放开的一侧（个人工资单、请假申请、
 * 候诊/医生队列、生日祝福、满意度调查）在对应控制器中修正，菜单保持原样。
 */
return new class extends Migration
{
    /** 菜单 title_key => 应当对齐的权限 slug（null 表示不限权限） */
    private const ALIGNMENTS = [
        // 待审批折扣：审批折扣属于改单据，医生/前台只有 view-invoices 不该看到入口
        'menu.pending_discount_approvals' => ['from' => 'view-invoices',   'to' => 'edit-invoices'],

        // 项目耗材设置：ServiceConsumableController 要求 manage-medical-services，
        // 库管只有 manage-inventory，菜单标错导致库管点进去 403
        'inventory.service_consumables'   => ['from' => 'manage-inventory', 'to' => 'manage-medical-services'],

        // 今日工作：TodayWorkController 要求 view-appointments，菜单原为「全员可见」，
        // 库管没有该权限 → 登录落地页直接 403
        'menu.today_work'                 => ['from' => null,               'to' => 'view-appointments'],

        // 医生绩效/工作量报表：DoctorReportController 明确放行 view-own-doctor-report，
        // 但菜单卡 view-reports，导致医生看不到自己的绩效报表（功能形同虚设）
        'menu.group_performance'          => ['from' => 'view-reports',     'to' => 'view-own-doctor-report'],
        'menu.doctor_performance_report'  => ['from' => 'view-reports',     'to' => 'view-own-doctor-report'],
        'menu.doctor_workload_report'     => ['from' => 'view-reports',     'to' => 'view-own-doctor-report'],
    ];

    public function up(): void
    {
        $permIds = DB::table('permissions')->pluck('id', 'slug');

        foreach (self::ALIGNMENTS as $titleKey => $move) {
            $toId = $permIds[$move['to']] ?? null;
            if ($toId === null) {
                continue; // 权限不存在则跳过，避免把菜单写成 null 而全员可见
            }

            DB::table('menu_items')
                ->where('title_key', $titleKey)
                ->update(['permission_id' => $toId]);
        }

        // 绩效报表菜单改用 view-own-doctor-report 后，管理员必须也持有该权限，
        // 否则管理员会反过来丢失这三个菜单入口（超管在 Gate::before 中全通过，
        // 但 MenuService 直接查 role_permissions，因此超管也需显式持有）。
        $ownReportId = $permIds['view-own-doctor-report'] ?? null;
        if ($ownReportId !== null) {
            $roleIds = DB::table('roles')->whereIn('slug', ['super-admin', 'admin'])->pluck('id');
            foreach ($roleIds as $roleId) {
                DB::table('role_permissions')->insertOrIgnore([
                    'role_id'       => $roleId,
                    'permission_id' => $ownReportId,
                ]);
                Cache::forget("role:{$roleId}:permissions");
            }
        }

        Cache::forget('menu_tree:all');
    }

    public function down(): void
    {
        $permIds = DB::table('permissions')->pluck('id', 'slug');

        foreach (self::ALIGNMENTS as $titleKey => $move) {
            $fromId = $move['from'] === null ? null : ($permIds[$move['from']] ?? null);

            DB::table('menu_items')
                ->where('title_key', $titleKey)
                ->update(['permission_id' => $fromId]);
        }

        // 管理员的 view-own-doctor-report 为本迁移新增，回滚时移除；超管本就持有全部权限，保留。
        $ownReportId = $permIds['view-own-doctor-report'] ?? null;
        $adminId     = DB::table('roles')->where('slug', 'admin')->value('id');
        if ($ownReportId !== null && $adminId !== null) {
            DB::table('role_permissions')
                ->where('role_id', $adminId)
                ->where('permission_id', $ownReportId)
                ->delete();
            Cache::forget("role:{$adminId}:permissions");
        }

        Cache::forget('menu_tree:all');
    }
};
