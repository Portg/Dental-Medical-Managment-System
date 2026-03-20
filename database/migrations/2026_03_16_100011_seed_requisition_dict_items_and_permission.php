<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 扩展出库单相关 DictItem，新增申领单所需权限及菜单项。
 * - stock_out_status: pending_approval / rejected
 * - stock_out_type: requisition / supplier_return / inventory_loss
 * - 新权限: request-inventory（医生申领）
 * - 新菜单项: 申领管理（Doctor + SuperAdmin/Admin 可见）
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- 扩展 stock_out_status DictItem ----
        $statusItems = [
            ['code' => 'pending_approval', 'name' => '待审批', 'sort_order' => 4],
            ['code' => 'rejected',         'name' => '已驳回', 'sort_order' => 5],
        ];
        foreach ($statusItems as $item) {
            $exists = DB::table('dict_items')
                ->where('type', 'stock_out_status')
                ->where('code', $item['code'])
                ->exists();
            if (!$exists) {
                DB::table('dict_items')->insert([
                    'type'       => 'stock_out_status',
                    'code'       => $item['code'],
                    'name'       => $item['name'],
                    'sort_order' => $item['sort_order'],
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // ---- 扩展 stock_out_type DictItem ----
        $typeItems = [
            ['code' => 'requisition',    'name' => '科室申领',  'sort_order' => 5],
            ['code' => 'supplier_return','name' => '供应商退货', 'sort_order' => 6],
            ['code' => 'inventory_loss', 'name' => '盘点损益',  'sort_order' => 7],
        ];
        foreach ($typeItems as $item) {
            $exists = DB::table('dict_items')
                ->where('type', 'stock_out_type')
                ->where('code', $item['code'])
                ->exists();
            if (!$exists) {
                DB::table('dict_items')->insert([
                    'type'       => 'stock_out_type',
                    'code'       => $item['code'],
                    'name'       => $item['name'],
                    'sort_order' => $item['sort_order'],
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // ---- 新增权限: request-inventory ----
        $permExists = DB::table('permissions')->where('slug', 'request-inventory')->exists();
        if (!$permExists) {
            $permId = DB::table('permissions')->insertGetId([
                'name'        => 'Request Inventory',
                'slug'        => 'request-inventory',
                'description' => '申领库存物品（医生）',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // 绑定到 Doctor 角色
            $doctorRoleId = DB::table('roles')->where('slug', 'doctor')->value('id');
            if ($doctorRoleId) {
                DB::table('role_permissions')->insertOrIgnore([
                    'role_id'       => $doctorRoleId,
                    'permission_id' => $permId,
                ]);
            }
        } else {
            $permId = DB::table('permissions')->where('slug', 'request-inventory')->value('id');
        }

        // ---- 新增菜单项: 申领管理 ----
        if (DB::table('menu_items')->where('title_key', 'menu.requisition_management')->exists()) {
            return;
        }

        // 找库存管理父节点（title_key = menu.group_inventory）
        $inventoryGroup = DB::table('menu_items')
            ->where('title_key', 'menu.group_inventory')
            ->first();

        // 申领管理菜单需双权限（OR）：用 manage-inventory 存储，Controller 内另做角色过滤
        $menuItemId = DB::table('menu_items')->insertGetId([
            'parent_id'     => $inventoryGroup ? $inventoryGroup->id : null,
            'title_key'     => 'menu.requisition_management',
            'url'           => 'requisitions',
            'icon'          => 'fa fa-file-text-o',
            'permission_id' => $permId,   // request-inventory
            'sort_order'    => 90,
            'is_active'     => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // 绑定 Doctor / SuperAdmin / Admin 角色
        $roleIds = DB::table('roles')
            ->whereIn('slug', ['super-admin', 'admin', 'doctor'])
            ->pluck('id');
        foreach ($roleIds as $roleId) {
            DB::table('role_menu_items')->insertOrIgnore([
                'role_id'      => $roleId,
                'menu_item_id' => $menuItemId,
            ]);
        }

        Cache::forget('menu_tree:all');
    }

    public function down(): void
    {
        // DictItems
        $codes = ['pending_approval', 'rejected'];
        DB::table('dict_items')->where('type', 'stock_out_status')->whereIn('code', $codes)->delete();

        $codes = ['requisition', 'supplier_return', 'inventory_loss'];
        DB::table('dict_items')->where('type', 'stock_out_type')->whereIn('code', $codes)->delete();

        // Menu
        $item = DB::table('menu_items')->where('title_key', 'menu.requisition_management')->first();
        if ($item) {
            DB::table('role_menu_items')->where('menu_item_id', $item->id)->delete();
            DB::table('menu_items')->where('id', $item->id)->delete();
        }

        // Permission
        $perm = DB::table('permissions')->where('slug', 'request-inventory')->first();
        if ($perm) {
            DB::table('role_permissions')->where('permission_id', $perm->id)->delete();
            DB::table('permissions')->where('id', $perm->id)->delete();
        }

        Cache::forget('menu_tree:all');
    }
};
