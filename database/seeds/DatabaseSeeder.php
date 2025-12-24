<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * 运行数据库种子
     * 填充系统所需的所有基础数据（中国地区版本）
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('========================================');
        $this->command->info('开始填充数据库基础数据...');
        $this->command->info('========================================');
        $this->command->line('');

        // 按照依赖关系顺序执行种子
        // 1. 角色（无依赖）
        $this->call(RolesTableSeeder::class);

        // 2. 用户（依赖角色）
        $this->call(UsersTableSeeder::class);

        // 3. 分支机构（依赖用户）
        $this->call(BranchesTableSeeder::class);

        // 4. 休假类型（无依赖）
        $this->call(LeaveTypesTableSeeder::class);

        // 5. 费用分类（依赖用户）
        $this->call(ExpenseCategoriesTableSeeder::class);

        // 6. 会计科目（依赖用户）
        $this->call(ChartOfAccountsTableSeeder::class);

        // 7. 医疗服务（依赖用户）
        $this->call(MedicalServicesTableSeeder::class);

        // 8. 保险公司（依赖用户）
        $this->call(InsuranceCompaniesTableSeeder::class);

        // 9. 索赔率（依赖保险公司和医疗服务）
        $this->call(ClaimRatesTableSeeder::class);

        // 10. 假期（无依赖）
        $this->call(HolidaysTableSeeder::class);

        // 11. 会计方程式（无依赖）
        $this->call(AccountingEquationsTableSeeder::class);

        // 可选：患者数据（用于测试）
        // $this->call(PatientsTableSeeder::class);

        $this->command->line('');
        $this->command->info('========================================');
        $this->command->info('数据库基础数据填充完成！');
        $this->command->info('========================================');
        $this->command->line('');
        $this->command->info('数据汇总:');
        $this->command->info('  ✓ 7个用户角色');
        $this->command->info('  ✓ 4个默认用户账户');
        $this->command->info('  ✓ 3个分支机构');
        $this->command->info('  ✓ 8种休假类型');
        $this->command->info('  ✓ 12个费用分类');
        $this->command->info('  ✓ 5个会计科目分类（29个科目项目）');
        $this->command->info('  ✓ 15种医疗服务（人民币定价）');
        $this->command->info('  ✓ 6家保险公司');
        $this->command->info('  ✓ 90条索赔率配置');
        $this->command->info('  ✓ 13个法定节假日');
        $this->command->info('  ✓ 4条会计方程式');
        $this->command->line('');
        $this->command->info('默认登录凭证:');
        $this->command->info('  管理员: admin@dental.com / password');
        $this->command->info('  医生: doctor@dental.com / password');
        $this->command->info('  护士: nurse@dental.com / password');
        $this->command->info('  前台: reception@dental.com / password');
        $this->command->line('');
        $this->command->warn('⚠ 重要提示: 请在首次登录后立即修改默认密码！');
        $this->command->info('========================================');
    }
}
