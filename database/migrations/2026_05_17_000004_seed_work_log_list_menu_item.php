<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 在"患者档案"组下新增"工作日志"菜单项（列表页），紧跟工作日志识别之后。
 * 面包屑自动显示"患者中心 / 工作日志"。
 */
return new class extends Migration
{
    public function up(): void
    {
        $parent = DB::table('menu_items')
            ->where('title_key', 'menu.group_patient_management')
            ->first();

        if (!$parent) {
            return;
        }

        $exists = DB::table('menu_items')
            ->where('title_key', 'menu.work_log')
            ->exists();

        if ($exists) {
            return;
        }

        $permId = DB::table('permissions')
            ->where('slug', 'create-patients')
            ->value('id');

        $menuItemId = DB::table('menu_items')->insertGetId([
            'parent_id'     => $parent->id,
            'title_key'     => 'menu.work_log',
            'url'           => 'work-logs',
            'icon'          => null,
            'permission_id' => $permId,
            'sort_order'    => 17,
            'is_active'     => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $roleIds = DB::table('roles')
            ->whereIn('slug', ['super-admin', 'admin', 'receptionist'])
            ->pluck('id');

        $pivotRows = [];
        foreach ($roleIds as $roleId) {
            $pivotRows[] = [
                'role_id'      => $roleId,
                'menu_item_id' => $menuItemId,
            ];
        }

        if ($pivotRows) {
            DB::table('role_menu_items')->insert($pivotRows);
        }

        Cache::forget('menu_tree:all');
    }

    public function down(): void
    {
        $menuItem = DB::table('menu_items')
            ->where('title_key', 'menu.work_log')
            ->first();

        if ($menuItem) {
            DB::table('role_menu_items')->where('menu_item_id', $menuItem->id)->delete();
            DB::table('menu_items')->where('id', $menuItem->id)->delete();
            Cache::forget('menu_tree:all');
        }
    }
};
