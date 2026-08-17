<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 把「收款」从「开单」里拆出来。
 *
 * 现状：create-invoices 这一条权限同时管着两件事 ——
 *   InvoiceController:        create / store / createBilling（开单、划价）
 *   InvoicePaymentController: store / storeMixed（实际收钱）
 * 而正式权限种子里医生只有 view-invoices，于是医生既不能开单、也不能收款，
 * 连点「转前台收费」都不行（那个也走 createBilling）。想表达牙科最常见的
 * 「医生划价、前台收费」分工，系统里根本没有对应的开关。
 *
 * 这个分法不是自创：e看牙把「创建收费」和「收费」分成两条权限，牙医管家的
 * 官方说明也是「医生在手机上处置划价，前台完成收费」——行业标准分工。
 *
 * 拆成：
 *   create-invoices   开单 / 划价 / 转前台待收（医生该有的那一半）
 *   collect-payments  实际收钱：现金、微信、储值等（前台的那一半）
 *
 * 本迁移**不改变任何人现在能做的事**：collect-payments 按「当前实际持有
 * create-invoices 的角色」回填，而不是写死角色名 —— 客户环境里自定义过的
 * 收费角色（比如单独的「收银」角色）同样能拿到，不会因为不在硬编码列表里
 * 而丢掉收款能力。
 *
 * 要不要让医生开单，是发不发 create-invoices 的事，请在「角色权限」界面里做，
 * 本迁移刻意不代劳：那是实质的业务变化（今天医生碰不到账单），
 * 不该藏在一个迁移里悄悄生效。
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $createInvoices = DB::table('permissions')->where('slug', 'create-invoices')->value('id');

        $collectId = DB::table('permissions')->where('slug', 'collect-payments')->value('id')
            ?: DB::table('permissions')->insertGetId([
                'name'        => '收款',
                'slug'        => 'collect-payments',
                'module'      => '账单管理',
                'description' => '登记收款（现金/微信/储值等）。与开单分开：开单是 create-invoices',
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

        if (!$createInvoices) {
            // 全新库、权限种子还没跑：什么都不回填，交给 DefaultRolePermissionsSeeder
            return;
        }

        // 按「当前谁能开单」回填「谁能收款」，保持现有行为完全不变
        $roleIds = DB::table('role_permissions')
            ->where('permission_id', $createInvoices)
            ->pluck('role_id');

        foreach ($roleIds as $roleId) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id'       => $roleId,
                'permission_id' => $collectId,
            ]);
            // Role::hasPermission() 按角色缓存权限 slug 列表，必须失效
            Cache::forget("role:{$roleId}:permissions");
        }
    }

    public function down(): void
    {
        // 不删 collect-payments，也不撤销它的角色授权。
        //
        // up() 对权限用「有则复用、无则新建」、对授权用 insertOrIgnore，迁移跑完就
        // 无从分辨哪些是自己新增的、哪些是客户环境本来就有的。无条件删除会连带撤销
        // 迁移前就存在的配置 —— 留下多余的权限行只是冗余，删掉别人的权限是数据损坏。
        // 同 2026_08_02_135903 的处理。
        //
        // 唯一必须做的是让缓存失效：控制器回退到 create-invoices 判定之后，
        // 角色权限列表的缓存里还留着 collect-payments，不清会读到过期数据。
        $roleIds = DB::table('roles')->pluck('id');

        foreach ($roleIds as $roleId) {
            Cache::forget("role:{$roleId}:permissions");
        }
    }
};
