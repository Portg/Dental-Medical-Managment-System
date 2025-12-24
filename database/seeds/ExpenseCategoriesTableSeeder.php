<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseCategoriesTableSeeder extends Seeder
{
    /**
     * 运行数据库种子
     * 填充费用分类数据
     *
     * @return void
     */
    public function run()
    {
        // 清空现有数据
        DB::table('expense_categories')->truncate();

        // 插入费用分类
        $expenseCategories = [
            ['id' => 1, 'name' => '水电费', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => '房租', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => '工资薪酬', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => '医疗耗材', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => '设备维护', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => '市场营销', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => '保险费用', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => '交通费', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'name' => '通讯费', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'name' => '办公用品', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => '培训费用', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'name' => '其他费用', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('expense_categories')->insert($expenseCategories);

        $this->command->info('✓ 已创建 12 个费用分类');
    }
}
