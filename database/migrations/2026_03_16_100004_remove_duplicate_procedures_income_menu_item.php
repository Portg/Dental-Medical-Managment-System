<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 删除冗余菜单项 menu.procedures_income_report。
 *
 * 原因：治疗项目收入报表（procedure-income-report）已合并为
 * 收费报表（billing-report）的 "项目收入" tab，
 * 两条菜单项同时指向 billing-report 构成重复，保留 general_income_report 即可。
 */
return new class extends Migration
{
    public function up(): void
    {
        $item = DB::table('menu_items')
            ->where('title_key', 'menu.procedures_income_report')
            ->first();

        if ($item) {
            DB::table('role_menu_items')->where('menu_item_id', $item->id)->delete();
            DB::table('menu_items')->where('id', $item->id)->delete();
        }

        Cache::forget('menu_tree:all');
    }

    public function down(): void
    {
        // 如需还原，重新插入（sort_order=20 在营收分析组下）
        $revGroup = DB::table('menu_items')
            ->where('title_key', 'menu.group_revenue_analysis')
            ->first();

        if (!$revGroup) {
            return;
        }

        $permId = DB::table('permissions')->where('slug', 'view-reports')->value('id');

        $menuItemId = DB::table('menu_items')->insertGetId([
            'parent_id'     => $revGroup->id,
            'title_key'     => 'menu.procedures_income_report',
            'url'           => 'billing-report',
            'icon'          => null,
            'permission_id' => $permId,
            'sort_order'    => 20,
            'is_active'     => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $roleIds = DB::table('roles')->whereIn('slug', ['super-admin', 'admin'])->pluck('id');
        foreach ($roleIds as $roleId) {
            DB::table('role_menu_items')->insert(['role_id' => $roleId, 'menu_item_id' => $menuItemId]);
        }

        Cache::forget('menu_tree:all');
    }
};
