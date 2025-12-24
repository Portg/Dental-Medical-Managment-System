<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicalServicesTableSeeder extends Seeder
{
    /**
     * 运行数据库种子
     * 填充医疗服务项目数据（价格单位：人民币元）
     *
     * @return void
     */
    public function run()
    {
        // 清空现有数据
        DB::table('medical_services')->truncate();

        // 插入医疗服务项目
        $services = [
            ['id' => 1, 'name' => '口腔检查', 'price' => 50.00, '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => '洁牙（洗牙）', 'price' => 150.00, '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => '拔牙', 'price' => 200.00, '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => '补牙（树脂充填）', 'price' => 300.00, '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => '根管治疗', 'price' => 800.00, '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => '烤瓷牙冠', 'price' => 1500.00, '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => '全瓷牙冠', 'price' => 2500.00, '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => '牙齿美白', 'price' => 800.00, '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'name' => '口腔X光片', 'price' => 80.00, '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'name' => '口腔CT', 'price' => 300.00, '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => '牙齿矫正咨询', 'price' => 100.00, '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'name' => '种植牙', 'price' => 8000.00, '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'name' => '牙周治疗', 'price' => 500.00, '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 14, 'name' => '儿童涂氟', 'price' => 80.00, '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 15, 'name' => '窝沟封闭', 'price' => 120.00, '_who_added' => 1, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('medical_services')->insert($services);

        $this->command->info('✓ 已创建 15 种医疗服务（价格单位：人民币元）');
    }
}
