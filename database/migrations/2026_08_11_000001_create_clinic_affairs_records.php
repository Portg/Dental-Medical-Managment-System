<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const VIEW_ROLES = ['super-admin', 'admin', 'doctor', 'nurse'];
    private const MANAGE_ROLES = ['super-admin', 'admin', 'nurse'];

    public function up(): void
    {
        Schema::create('clinic_disinfection_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('area', 100);
            $table->enum('check_type', ['clinical_surface', 'housekeeping', 'waterline', 'air_quality', 'other']);
            $table->string('disinfectant', 100)->nullable();
            $table->string('concentration', 50)->nullable();
            $table->dateTime('performed_at');
            $table->enum('result', ['pass', 'issue'])->default('pass');
            $table->text('corrective_action')->nullable();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'performed_at']);
            $table->index(['result', 'reviewed_at']);
        });

        Schema::create('equipment_maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('equipment_code', 50);
            $table->string('equipment_name', 100);
            $table->enum('category', ['xray', 'sterilizer', 'dental_unit', 'emergency', 'monitoring', 'other']);
            $table->string('location', 100)->nullable();
            $table->enum('maintenance_type', ['inspection', 'preventive', 'repair', 'calibration']);
            $table->dateTime('performed_at');
            $table->date('next_due_at')->nullable();
            $table->enum('result', ['normal', 'follow_up', 'out_of_service'])->default('normal');
            $table->string('vendor', 100)->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'equipment_code']);
            $table->index(['next_due_at', 'result']);
        });

        Schema::create('medical_waste_handover_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->enum('waste_type', ['infectious', 'sharps', 'pharmaceutical', 'chemical', 'other']);
            $table->decimal('weight_kg', 10, 2);
            $table->unsignedInteger('package_count')->default(1);
            $table->dateTime('handed_over_at');
            $table->foreignId('handler_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('receiver_name', 100);
            $table->string('carrier', 150)->nullable();
            $table->string('manifest_no', 100)->nullable();
            $table->string('destination', 200)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'handed_over_at']);
            $table->index('manifest_no');
        });

        $now = now();
        $permissions = [
            'view-clinic-affairs' => [
                'name' => '查看诊所事务',
                'description' => '查看环境消毒、设备维护和医疗废物交接记录',
                'roles' => self::VIEW_ROLES,
            ],
            'manage-clinic-affairs' => [
                'name' => '管理诊所事务',
                'description' => '新增、修改、复核和删除诊所事务记录',
                'roles' => self::MANAGE_ROLES,
            ],
        ];

        $permissionIds = [];
        foreach ($permissions as $slug => $definition) {
            $permissionIds[$slug] = DB::table('permissions')->where('slug', $slug)->value('id')
                ?: DB::table('permissions')->insertGetId([
                    'name' => $definition['name'],
                    'slug' => $slug,
                    'module' => '诊所事务',
                    'description' => $definition['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

            $roleIds = DB::table('roles')->whereIn('slug', $definition['roles'])->pluck('id');
            foreach ($roleIds as $roleId) {
                DB::table('role_permissions')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permissionIds[$slug],
                ]);
                Cache::forget("role:{$roleId}:permissions");
            }
        }

        /*
         * 挂菜单子项。这个 if 是有意的，不是漏判：
         *
         *   全新安装：migrate --seed 先跑迁移、后跑 seeder，此刻 menu_items 还是空的，
         *             $parentId 为 null，这三项静默跳过 —— 随后 PermissionsTableSeeder
         *             和 MenuItemsSeeder 会把权限和菜单一并建齐（见 MenuItemsSeeder 的
         *             clinicAffairs 分组），结果一致。
         *   存量升级：诊所机器上「诊所事务」父节点早就在了，走这里直接插入。
         *
         * 也就是说本段只服务升级路径，全新安装靠 seeder 兜底。别把 if 去掉改成
         * 强制建父节点 —— 那会和 MenuItemsSeeder 抢同一行的所有权（seeder 会
         * truncate 重建菜单，见 MenuItemsSeeder 顶部注释）。
         */
        $parentId = DB::table('menu_items')->where('title_key', 'menu.clinic_affairs')->value('id');
        if ($parentId) {
            foreach ([
                ['menu.disinfection_checks', 'clinic-affairs/disinfection', 20],
                ['menu.equipment_maintenance', 'clinic-affairs/equipment-maintenance', 30],
                ['menu.medical_waste_handover', 'clinic-affairs/medical-waste', 40],
            ] as [$titleKey, $url, $sortOrder]) {
                DB::table('menu_items')->updateOrInsert(
                    ['title_key' => $titleKey],
                    [
                        'parent_id' => $parentId,
                        'url' => $url,
                        'icon' => null,
                        'permission_id' => $permissionIds['view-clinic-affairs'],
                        'sort_order' => $sortOrder,
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }

        Cache::forget('menu_tree:all');
    }

    public function down(): void
    {
        /*
         * 有意不删 view-clinic-affairs / manage-clinic-affairs 及其角色授权。
         *
         * up() 对权限是「有则复用、无则新建」、对授权用 insertOrIgnore，跑完就无从
         * 分辨哪些行是本迁移新增的、哪些是客户环境本来就有的。无条件删除会连带撤销
         * 迁移之前就存在的配置。留下多余的权限行只是冗余，删掉别人的权限是数据损坏。
         * 同 2026_08_02_135903 的处理。
         *
         * 菜单项则相反：三项都由本迁移/seeder 独占，删干净不会留下 permission_id
         * 悬空的行（MenuService 把 null 当全员可见，那比现状授权更宽）。
         */
        DB::table('menu_items')->whereIn('title_key', [
            'menu.disinfection_checks',
            'menu.equipment_maintenance',
            'menu.medical_waste_handover',
        ])->delete();

        Cache::forget('menu_tree:all');

        Schema::dropIfExists('medical_waste_handover_records');
        Schema::dropIfExists('equipment_maintenance_records');
        Schema::dropIfExists('clinic_disinfection_records');
    }
};
