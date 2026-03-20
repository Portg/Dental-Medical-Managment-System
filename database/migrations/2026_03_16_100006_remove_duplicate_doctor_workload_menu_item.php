<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 删除冗余菜单项 menu.doctor_workload_report。
 *
 * 原因：医生工作量报表（doctor-workload-report）已合并为
 * 医生绩效报表（doctor-report）的 "工作量" tab，
 * 两条菜单项同时指向 doctor-report 构成重复，保留 doctor_performance_report 即可。
 */
return new class extends Migration
{
    public function up(): void
    {
        $item = DB::table('menu_items')
            ->where('title_key', 'menu.doctor_workload_report')
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
            'title_key'     => 'menu.doctor_workload_report',
            'url'           => 'doctor-report',
            'icon'          => null,
            'permission_id' => $permId,
            'sort_order'    => 60,
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
