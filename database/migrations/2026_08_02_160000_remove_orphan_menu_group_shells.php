<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 清掉三个空壳一级菜单：财务中心 / 仓储管理 / 人事行政。
 *
 * 来历：2026_03_16_100020 的 up() 建了这三个一级分组并把子项挂了进去，
 * 后续菜单重构又把子项迁到了 operations_center / data_center / system_settings，
 * 这三个壳子就空了下来——0 个子项、url 为 null、permission_id 为 null，
 * sort_order 还与真实分组撞车（40 / 50 / 60）。
 *
 * 长期升级过的库里它们已被清掉，但**全新安装**每次都会重新留下这三行。
 *
 * 界面上看不见：MenuService::filterByPermission 与 buildPreviewArray 都有
 * 「无 url 且无子项的目录节点不渲染」的判断，所以这不是显示缺陷，
 * 纯粹是数据残留——它会让 menu_items 的行数对不上、也会出现在
 * MenuItemsSeeder 的「存在于数据库但未在 Seeder 中定义」告警里。
 *
 * 只删**确实是空壳**的：仍有子项或已被赋予 url 的，说明有人重新启用了它，
 * 一律保留，不做猜测性删除。
 */
return new class extends Migration
{
    private const TITLE_KEYS = [
        'menu.financial_center',
        'menu.warehouse_management',
        'menu.hr_admin',
    ];

    public function up(): void
    {
        $candidates = DB::table('menu_items')
            ->whereIn('title_key', self::TITLE_KEYS)
            ->whereNull('url')
            ->pluck('title_key', 'id');

        if ($candidates->isEmpty()) {
            return;
        }

        // 仍挂着子项的说明被重新使用了，跳过
        $withChildren = DB::table('menu_items')
            ->whereIn('parent_id', $candidates->keys())
            ->distinct()
            ->pluck('parent_id');

        $removable = $candidates->except($withChildren->all());

        if ($removable->isEmpty()) {
            return;
        }

        $ids = $removable->keys()->all();

        // menu_items 上没有任何外键指向它，孤儿行不会被级联清掉，得手动删。
        // （role_menu_items 目前是死数据，侧边栏只认 menu_items.permission_id，
        //   但留着无主行没有意义。）
        if (Schema::hasTable('role_menu_items')) {
            DB::table('role_menu_items')->whereIn('menu_item_id', $ids)->delete();
        }

        DB::table('menu_items')->whereIn('id', $ids)->delete();

        Log::info(
            '[remove_orphan_menu_group_shells] 已删除 ' . count($ids) . ' 个空壳菜单分组：'
            . $removable->values()->implode('、')
        );

        Cache::forget('menu_tree:all');
    }

    public function down(): void
    {
        // 不重建。这三行是重构遗留的空壳，重新插回去只是把垃圾数据放回原处；
        // 且它们本就不渲染，缺了不影响任何功能。
        //
        // 注：2026_03_16_100020 的 down() 会按 title_key 找这三个节点、
        // 把子项迁回运营中心再删除。此处删除后它找不到节点会直接跳过——
        // 而能走到这条迁移的前提就是它们已经没有子项，那段回滚逻辑本就是空转。
    }
};
