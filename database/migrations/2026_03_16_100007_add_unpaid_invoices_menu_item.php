<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 新增"未收款报表"菜单项，放在营收分析组下。
 */
return new class extends Migration
{
    public function up(): void
    {
        // 幂等检查
        if (DB::table('menu_items')->where('title_key', 'menu.unpaid_invoices_report')->exists()) {
            return;
        }

        $revGroup = DB::table('menu_items')
            ->where('title_key', 'menu.group_revenue_analysis')
            ->first();

        if (!$revGroup) {
            return;
        }

        $permId = DB::table('permissions')->where('slug', 'view-reports')->value('id');

        $menuItemId = DB::table('menu_items')->insertGetId([
            'parent_id'     => $revGroup->id,
            'title_key'     => 'menu.unpaid_invoices_report',
            'url'           => 'unpaid-invoices',
            'icon'          => null,
            'permission_id' => $permId,
            'sort_order'    => 40,
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

    public function down(): void
    {
        $item = DB::table('menu_items')->where('title_key', 'menu.unpaid_invoices_report')->first();
        if ($item) {
            DB::table('role_menu_items')->where('menu_item_id', $item->id)->delete();
            DB::table('menu_items')->where('id', $item->id)->delete();
        }
        Cache::forget('menu_tree:all');
    }
};
