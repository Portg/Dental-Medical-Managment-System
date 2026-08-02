<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 病历相关菜单改挂 view-medical-cases。
 *
 * 2026_08_02_140314 把病历拆成读写两权并收回了护士的 manage-medical-cases，
 * 但没动 menu_items —— 「病历」分组与其下的病历列表、牙位图三项仍挂着 manage-。
 *
 * 后果：护士升级后持有 view-medical-cases、路由也放行只读动作，侧边栏里却
 * 整组消失，等于拿不到入口。AuthServiceProvider 的 Gate::before 有一条
 * 「manage-x 蕴含 view-x」，但那只对 Gate 生效；MenuService 走的是
 * $user->hasPermission($slug) 直查，不经过 Gate，而且蕴含方向也是反的
 * （持有 view- 推不出 manage-）。
 *
 * 只改这三项；处方（manage-treatments）等其余菜单不在本次拆分范围内。
 * 持有 manage-medical-cases 的角色都已显式获授 view-medical-cases
 * （140314 的 VIEW_ROLES 与 DefaultRolePermissionsSeeder 都补了），
 * 因此改挂之后没有人会因此丢可见性。
 */
return new class extends Migration
{
    private const URLS = ['medical-cases', 'dental-charting'];

    public function up(): void
    {
        $this->repoint('manage-medical-cases', 'view-medical-cases');
    }

    public function down(): void
    {
        $this->repoint('view-medical-cases', 'manage-medical-cases');
    }

    private function repoint(string $fromSlug, string $toSlug): void
    {
        $fromId = DB::table('permissions')->where('slug', $fromSlug)->value('id');
        $toId   = DB::table('permissions')->where('slug', $toSlug)->value('id');

        // 目标权限不存在就什么都不做：宁可保持现状，也不能把 permission_id 写成
        // null —— MenuService 把 null 当作「全员可见」，那是比现状更宽的授权。
        if (!$fromId || !$toId) {
            return;
        }

        DB::table('menu_items')
            ->whereIn('url', self::URLS)
            ->where('permission_id', $fromId)
            ->update(['permission_id' => $toId, 'updated_at' => now()]);

        Cache::forget('menu_tree:all');
    }
};
