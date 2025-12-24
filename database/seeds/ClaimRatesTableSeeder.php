<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClaimRatesTableSeeder extends Seeder
{
    /**
     * 运行数据库种子
     * 填充保险索赔率数据
     * 基础诊疗项目70%，中等诊疗项目60%，高端诊疗项目50%
     *
     * @return void
     */
    public function run()
    {
        // 清空现有数据
        DB::table('claim_rates')->truncate();

        // 基础诊疗项目（索赔率70%）
        $basicServices = [1, 2, 3, 4, 9, 13, 14, 15];

        // 中等诊疗项目（索赔率60%）
        $mediumServices = [5, 6, 8, 11];

        // 高端诊疗项目（索赔率50%）
        $advancedServices = [7, 10, 12];

        $claimRates = [];

        // 为每个保险公司和医疗服务设置索赔率
        for ($insuranceId = 1; $insuranceId <= 6; $insuranceId++) {
            for ($serviceId = 1; $serviceId <= 15; $serviceId++) {
                $rate = 50.00; // 默认高端项目索赔率

                if (in_array($serviceId, $basicServices)) {
                    $rate = 70.00;
                } elseif (in_array($serviceId, $mediumServices)) {
                    $rate = 60.00;
                }

                $claimRates[] = [
                    'insurance_company_id' => $insuranceId,
                    'medical_service_id' => $serviceId,
                    'rate' => $rate,
                    '_who_added' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // 批量插入
        DB::table('claim_rates')->insert($claimRates);

        $this->command->info('✓ 已创建 90 条索赔率配置（6家保险公司 × 15种医疗服务）');
        $this->command->info('  - 基础诊疗项目: 70%');
        $this->command->info('  - 中等诊疗项目: 60%');
        $this->command->info('  - 高端诊疗项目: 50%');
    }
}
