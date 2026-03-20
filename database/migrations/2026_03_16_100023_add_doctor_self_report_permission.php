<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 医生自查绩效报表：
 * 1. 新增 view-own-doctor-report 权限
 * 2. 授予医生角色
 * 3. 在诊疗中心下新增"我的绩效"菜单项（仅医生可见）
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // 1. 创建权限
        $permId = DB::table('permissions')->insertGetId([
            'name'        => '查看个人绩效报表',
            'slug'        => 'view-own-doctor-report',
            'module'      => '报表管理',
            'description' => '医生查看自己的绩效和工作量数据',
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        // 2. 授予医生角色
        $doctorRole = DB::table('roles')->where('slug', 'doctor')->first();
        if ($doctorRole) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id'       => $doctorRole->id,
                'permission_id' => $permId,
            ]);
        }

        // 超管自动获得所有权限（seeder 会处理，但迁移也补一下）
        $superAdmin = DB::table('roles')->where('slug', 'super-admin')->first();
        if ($superAdmin) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id'       => $superAdmin->id,
                'permission_id' => $permId,
            ]);
        }

        // 3. 在诊疗中心下新增"我的绩效"菜单项
        $clinicalCenter = DB::table('menu_items')
            ->where('title_key', 'menu.clinical_center')
            ->first();

        if ($clinicalCenter) {
            $menuId = DB::table('menu_items')->insertGetId([
                'parent_id'     => $clinicalCenter->id,
                'title_key'     => 'menu.my_performance',
                'url'           => 'doctor-report',
                'icon'          => null,
                'permission_id' => $permId,
                'sort_order'    => 90, // 排在诊疗中心子项靠后
                'is_active'     => true,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            // 为医生角色添加 role_menu_items
            if ($doctorRole) {
                DB::table('role_menu_items')->insertOrIgnore([
                    'role_id'      => $doctorRole->id,
                    'menu_item_id' => $menuId,
                ]);
            }
        }

        Cache::forget('menu_tree:all');
    }

    public function down(): void
    {
        // 删除菜单项
        $menuItem = DB::table('menu_items')
            ->where('title_key', 'menu.my_performance')
            ->first();

        if ($menuItem) {
            DB::table('role_menu_items')->where('menu_item_id', $menuItem->id)->delete();
            DB::table('menu_items')->where('id', $menuItem->id)->delete();
        }

        // 删除权限
        $perm = DB::table('permissions')->where('slug', 'view-own-doctor-report')->first();
        if ($perm) {
            DB::table('role_permissions')->where('permission_id', $perm->id)->delete();
            DB::table('permissions')->where('id', $perm->id)->delete();
        }

        Cache::forget('menu_tree:all');
    }
};
