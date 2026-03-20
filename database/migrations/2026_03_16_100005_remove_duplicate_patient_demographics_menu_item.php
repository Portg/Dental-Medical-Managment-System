<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 删除冗余菜单项 menu.patient_demographics_report。
 *
 * 原因：患者画像报表（patient-demographics-report）已合并为
 * 患者分析报表（patient-report）的 "人口统计" tab，
 * 两条菜单项同时指向 patient-report 构成重复，保留 patient_source_report 即可。
 */
return new class extends Migration
{
    public function up(): void
    {
        $item = DB::table('menu_items')
            ->where('title_key', 'menu.patient_demographics_report')
            ->first();

        if ($item) {
            DB::table('role_menu_items')->where('menu_item_id', $item->id)->delete();
            DB::table('menu_items')->where('id', $item->id)->delete();
        }

        Cache::forget('menu_tree:all');
    }

    public function down(): void
    {
        $bizGroup = DB::table('menu_items')
            ->where('title_key', 'menu.group_business_analysis')
            ->first();

        if (!$bizGroup) {
            return;
        }

        $permId = DB::table('permissions')->where('slug', 'view-reports')->value('id');

        $menuItemId = DB::table('menu_items')->insertGetId([
            'parent_id'     => $bizGroup->id,
            'title_key'     => 'menu.patient_demographics_report',
            'url'           => 'patient-report',
            'icon'          => null,
            'permission_id' => $permId,
            'sort_order'    => 70,
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
