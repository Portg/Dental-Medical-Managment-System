<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsuranceCompaniesTableSeeder extends Seeder
{
    /**
     * 运行数据库种子
     * 填充保险公司数据（中国主要保险公司）
     *
     * @return void
     */
    public function run()
    {
        // 清空现有数据
        DB::table('insurance_companies')->truncate();

        // 插入保险公司
        $companies = [
            [
                'id' => 1,
                'name' => '中国人寿保险',
                'email' => 'service@chinalife.com.cn',
                'phone_no' => '95519',
                '_who_added' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => '中国平安保险',
                'email' => 'service@pingan.com',
                'phone_no' => '95511',
                '_who_added' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => '中国太平洋保险',
                'email' => 'service@cpic.com.cn',
                'phone_no' => '95500',
                '_who_added' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => '中国人民保险',
                'email' => 'service@picc.com.cn',
                'phone_no' => '95518',
                '_who_added' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'name' => '泰康人寿',
                'email' => 'service@taikang.com',
                'phone_no' => '95522',
                '_who_added' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'name' => '新华人寿',
                'email' => 'service@newchinalife.com',
                'phone_no' => '95567',
                '_who_added' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('insurance_companies')->insert($companies);

        $this->command->info('✓ 已创建 6 家保险公司');
    }
}