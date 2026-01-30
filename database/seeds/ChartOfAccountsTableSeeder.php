<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsTableSeeder extends Seeder
{
    /**
     * 运行数据库种子
     * 填充会计科目分类和科目项目数据
     *
     * @return void
     */
    public function run()
    {
        // 清空现有数据
        DB::table('chart_of_account_items')->truncate();
        DB::table('chart_of_account_categories')->truncate();

        // 插入会计科目分类
        $categories = [
            ['id' => 1, 'name' => '资产', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => '负债', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => '所有者权益', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => '收入', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => '费用', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('chart_of_account_categories')->insert($categories);

        // 插入会计科目项目
        $items = [
            // 资产类账户
            ['chart_of_account_category_id' => 1, 'name' => '库存现金', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 1, 'name' => '银行存款', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 1, 'name' => '应收账款', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 1, 'name' => '医疗设备', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 1, 'name' => '办公设备', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 1, 'name' => '医疗耗材库存', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 1, 'name' => '固定资产', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 1, 'name' => '无形资产', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],

            // 负债类账户
            ['chart_of_account_category_id' => 2, 'name' => '应付账款', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 2, 'name' => '应付工资', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 2, 'name' => '应交税费', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 2, 'name' => '短期借款', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 2, 'name' => '长期借款', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],

            // 所有者权益类账户
            ['chart_of_account_category_id' => 3, 'name' => '实收资本', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 3, 'name' => '资本公积', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 3, 'name' => '盈余公积', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 3, 'name' => '未分配利润', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],

            // 收入类账户
            ['chart_of_account_category_id' => 4, 'name' => '诊疗收入', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 4, 'name' => '医保收入', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 4, 'name' => '商保收入', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 4, 'name' => '其他业务收入', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],

            // 费用类账户
            ['chart_of_account_category_id' => 5, 'name' => '工资薪金', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 5, 'name' => '社保公积金', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 5, 'name' => '房租费用', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 5, 'name' => '水电费用', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 5, 'name' => '医疗耗材费用', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 5, 'name' => '设备折旧', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 5, 'name' => '管理费用', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['chart_of_account_category_id' => 5, 'name' => '销售费用', '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('chart_of_account_items')->insert($items);

        $this->command->info('✓ 已创建 5 个会计科目分类');
        $this->command->info('✓ 已创建 29 个会计科目项目');
    }
}