<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HolidaysTableSeeder extends Seeder
{
    /**
     * 运行数据库种子
     * 填充中国法定节假日数据（当前年份）
     *
     * @return void
     */
    public function run()
    {
        // 清空现有数据
        DB::table('holidays')->truncate();

        $currentYear = Carbon::now()->year;

        // 插入中国法定节假日
        // 注意：春节、清明、端午、中秋等节日的具体日期每年不同，这里使用示例日期
        $holidays = [
            ['id' => 1, 'name' => '元旦', 'holiday_date' => "{$currentYear}-01-01", 'created_at' => now(), 'updated_at' => now()],

            // 春节（农历新年，具体日期每年不同，这里使用示例日期）
            ['id' => 2, 'name' => '春节', 'holiday_date' => "{$currentYear}-02-10", 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => '春节', 'holiday_date' => "{$currentYear}-02-11", 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => '春节', 'holiday_date' => "{$currentYear}-02-12", 'created_at' => now(), 'updated_at' => now()],

            // 清明节（具体日期每年不同）
            ['id' => 5, 'name' => '清明节', 'holiday_date' => "{$currentYear}-04-04", 'created_at' => now(), 'updated_at' => now()],

            // 劳动节
            ['id' => 6, 'name' => '劳动节', 'holiday_date' => "{$currentYear}-05-01", 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => '劳动节', 'holiday_date' => "{$currentYear}-05-02", 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => '劳动节', 'holiday_date' => "{$currentYear}-05-03", 'created_at' => now(), 'updated_at' => now()],

            // 端午节（农历节日，具体日期每年不同）
            ['id' => 9, 'name' => '端午节', 'holiday_date' => "{$currentYear}-06-10", 'created_at' => now(), 'updated_at' => now()],

            // 中秋节（农历节日，具体日期每年不同）
            ['id' => 10, 'name' => '中秋节', 'holiday_date' => "{$currentYear}-09-17", 'created_at' => now(), 'updated_at' => now()],

            // 国庆节
            ['id' => 11, 'name' => '国庆节', 'holiday_date' => "{$currentYear}-10-01", 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'name' => '国庆节', 'holiday_date' => "{$currentYear}-10-02", 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'name' => '国庆节', 'holiday_date' => "{$currentYear}-10-03", 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('holidays')->insert($holidays);

        $this->command->info("✓ 已创建 {$currentYear} 年的 13 个法定节假日");
        $this->command->warn('⚠ 注意：农历节日日期仅为示例，请根据实际年份调整');
    }
}
