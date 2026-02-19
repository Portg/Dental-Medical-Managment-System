<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * 运行顺序说明：
     * 1. 基础配置（分支机构、角色、权限）
     * 2. 用户数据（依赖分支机构和角色）
     * 3. 角色权限关联
     * 4. 业务基础数据（服务项目、费用分类等）
     * 5. 辅助数据（节假日、模板等）
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('===========================================');
        $this->command->info('  牙科门诊管理系统 - 初始化数据');
        $this->command->info('===========================================');
        $this->command->info('');

        // ========== 1. 基础配置 ==========
        $this->command->info('[1/5] 初始化基础配置...');
        $this->call(BranchesTableSeeder::class);
        $this->call(RolesTableSeeder::class);
        $this->call(PermissionsTableSeeder::class);

        // ========== 2. 用户数据 ==========
        $this->command->info('[2/5] 创建系统用户...');
        $this->call(UsersTableSeeder::class);

        // ========== 3. 角色权限关联 ==========
        $this->command->info('[3/5] 配置角色权限...');
        $this->call(DefaultRolePermissionsSeeder::class);

        // ========== 4. 业务基础数据 ==========
        $this->command->info('[4/6] 初始化业务基础数据...');

        // 诊疗服务项目
        $this->call(MedicalServicesSeeder::class);

        // 费用分类
        $this->call(ExpenseCategoriesSeeder::class);

        // 库存分类
        $this->call(InventoryCategoriesSeeder::class);

        // 保险公司
        $this->call(InsuranceCompaniesTableSeeder::class);

        // 会计科目
        $this->call(ChartOfAccountsTableSeeder::class);

        // ========== 5. 诊所运营配置 ==========
        $this->command->info('[5/6] 初始化诊所运营配置...');

        // 诊室椅位
        $this->call(ChairsTableSeeder::class);

        // 会员等级
        $this->call(MemberLevelsSeeder::class);

        // 提成规则（示例）
        $this->call(CommissionRulesSeeder::class);

        // 优惠券（示例）
        $this->call(CouponsSeeder::class);

        // ========== 6. 辅助数据 ==========
        $this->command->info('[6/6] 初始化辅助数据...');

        // 休假类型
        $this->call(LeaveTypesTableSeeder::class);

        // 节假日
        $this->call(HolidaysTableSeeder::class);

        // 患者标签和来源
        $this->call(PatientTagsSeeder::class);

        // 病历模板和常用短语
        $this->call(MedicalTemplatesSeeder::class);

        // 快捷短语分类
        $this->call(QuickPhraseCategoriesSeeder::class);

        $this->command->info('');
        $this->command->info('===========================================');
        $this->command->info('  初始化完成！');
        $this->command->info('===========================================');
        $this->command->info('');
        $this->command->info('默认管理员账号: admin@example.com');
        $this->command->info('默认密码: password');
        $this->command->info('');
        $this->command->warn('请登录后及时修改默认密码！');
    }
}
