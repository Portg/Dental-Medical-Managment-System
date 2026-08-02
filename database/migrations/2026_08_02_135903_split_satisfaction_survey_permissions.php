<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 满意度调查：拆分查看与管理权限，并给 appointment_id 补唯一约束。
 *
 * 此前 SatisfactionSurveyController 整个控制器只挂 can:view-patients，
 * 而该权限授予了 admin / doctor / nurse / receptionist / super-admin 全部角色 ——
 * 于是任何能看患者的人都能批量生成问卷、重置患者填写链接。
 *
 * 拆成：
 *   - view-surveys   看板与列表（沿用原可见范围，不缩小任何人的可见性）
 *   - manage-surveys 生成问卷、批量生成、重置链接（前台为主力，见控制器注释）
 *
 * 另：satisfaction_surveys.appointment_id 只是普通可空列，
 * 而「一次就诊一份问卷」是关系与测试都声明过的约束。sendBatch 用
 * 「查不存在 → 创建」实现，并发下会生成多份。这里补唯一索引作为最后防线，
 * 服务层同步改为 firstOrCreate。
 */
return new class extends Migration
{
    private const VIEW_ROLES   = ['super-admin', 'admin', 'doctor', 'nurse', 'receptionist'];
    private const MANAGE_ROLES = ['super-admin', 'admin', 'receptionist'];

    public function up(): void
    {
        $now = now();

        $permIds = [];
        foreach ([
            'view-surveys'   => ['查看满意度调查', '查看满意度调查看板与问卷列表'],
            'manage-surveys' => ['管理满意度调查', '生成问卷、批量生成、重置患者填写链接'],
        ] as $slug => [$name, $description]) {
            $existing = DB::table('permissions')->where('slug', $slug)->value('id');

            $permIds[$slug] = $existing ?: DB::table('permissions')->insertGetId([
                'name'        => $name,
                'slug'        => $slug,
                'module'      => '满意度调查',
                'description' => $description,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        $this->grant($permIds['view-surveys'], self::VIEW_ROLES);
        $this->grant($permIds['manage-surveys'], self::MANAGE_ROLES);

        // 菜单项改挂 view-surveys（原先挂的是 view-patients）
        DB::table('menu_items')
            ->where('url', 'satisfaction-surveys')
            ->update(['permission_id' => $permIds['view-surveys'], 'updated_at' => $now]);

        $this->addAppointmentUniqueIndex();

        Cache::forget('menu_tree:all');
    }

    public function down(): void
    {
        // 菜单先改回 view-patients，避免删权限后菜单项挂空 permission_id
        // —— MenuService 把 null 视为「全员可见」，那是比现状更宽的授权。
        $viewPatients = DB::table('permissions')->where('slug', 'view-patients')->value('id');
        if ($viewPatients) {
            DB::table('menu_items')
                ->where('url', 'satisfaction-surveys')
                ->update(['permission_id' => $viewPatients, 'updated_at' => now()]);
        }

        // 不删 view-surveys / manage-surveys，也不删它们的角色授权。
        //
        // up() 对权限用「有则复用、无则新建」，对授权用 insertOrIgnore —— 迁移跑完
        // 就无从分辨哪些是自己新增的、哪些是客户环境本来就有的。无条件删除会连带
        // 撤销迁移前就存在的配置。同 2026_08_01_000002 的处理（见 c9c611e）。
        //
        // 留下多余的权限行只是冗余，删掉别人的权限是数据损坏，两害相权取其轻。

        if (Schema::hasTable('satisfaction_surveys')) {
            Schema::table('satisfaction_surveys', function (Blueprint $table) {
                $table->dropUnique('satisfaction_surveys_appointment_id_unique');
            });
        }

        $this->forgetRolePermissionCaches();
        Cache::forget('menu_tree:all');
    }

    private function grant(int $permId, array $roleSlugs): void
    {
        $roleIds = DB::table('roles')->whereIn('slug', $roleSlugs)->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id'       => $roleId,
                'permission_id' => $permId,
            ]);
            // Role::hasPermission() 按角色缓存权限 slug 列表，必须失效
            Cache::forget("role:{$roleId}:permissions");
        }
    }

    /**
     * 加唯一索引前先清理历史重复：同一预约保留**最有价值**的一份，
     * 其余解除预约关联（把 appointment_id 置 null）。
     *
     * 注意这里不是软删除：重复行本身仍然保留，只是不再挂在该预约上，
     * 便于事后人工核对；物理丢弃患者已填写的评价是不可接受的。
     *
     * 取舍顺序：已填写（completed）优先于未填写，同档再取最早的一条。
     * 不能简单取 MIN(id)——批量生成常常先建出一份 pending，患者后来填的是另一份，
     * 按 id 最小保留会把预约挂到空白问卷上，真正的评价反被摘掉关联。
     */
    private function addAppointmentUniqueIndex(): void
    {
        $duplicated = DB::table('satisfaction_surveys')
            ->whereNotNull('appointment_id')
            ->select('appointment_id', DB::raw('COUNT(*) as c'))
            ->groupBy('appointment_id')
            ->having('c', '>', 1)
            ->pluck('appointment_id');

        foreach ($duplicated as $appointmentId) {
            $keepId = DB::table('satisfaction_surveys')
                ->where('appointment_id', $appointmentId)
                ->orderByRaw("CASE WHEN status = 'completed' THEN 0 ELSE 1 END")
                ->orderByRaw('CASE WHEN survey_date IS NULL THEN 1 ELSE 0 END')
                ->orderBy('id')
                ->value('id');

            DB::table('satisfaction_surveys')
                ->where('appointment_id', $appointmentId)
                ->where('id', '<>', $keepId)
                ->update(['appointment_id' => null]);
        }

        Schema::table('satisfaction_surveys', function (Blueprint $table) {
            $table->unique('appointment_id');
        });
    }

    private function forgetRolePermissionCaches(): void
    {
        foreach (DB::table('roles')->pluck('id') as $roleId) {
            Cache::forget("role:{$roleId}:permissions");
        }
    }
};
