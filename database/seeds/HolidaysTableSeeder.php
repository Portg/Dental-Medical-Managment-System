<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HolidaysTableSeeder extends Seeder
{
    /**
     * 运行数据库种子
     * 填充2026年中国法定节假日数据
     *
     * 2026年中国法定节假日安排：
     * - 元旦：1月1日-1月3日（3天）
     * - 春节：1月26日-2月1日（农历除夕至初六，7天）
     * - 清明节：4月4日-4月6日（3天）
     * - 劳动节：5月1日-5月5日（5天）
     * - 端午节：5月30日-6月1日（3天）
     * - 中秋节与国庆节：10月1日-10月8日（8天）
     *
     * 注：2026年春节为农历丙午年正月初一，对应公历2026年1月27日
     *
     * @return void
     */
    public function run()
    {
        // 清空现有数据（禁用外键检查以避免truncate问题）
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('holidays')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 获取第一个管理员用户ID作为_who_added
        $adminId = DB::table('users')->where('role_id', 1)->value('id') ?? 1;

        $holidays = [];
        $id = 1;

        // ========== 元旦 ==========
        // 2026年1月1日-1月3日（3天）
        $holidays[] = [
            'id' => $id++,
            'name' => '元旦',
            'holiday_date' => '2026-01-01',
            'repeat_date' => 'No',
            '_who_added' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $holidays[] = [
            'id' => $id++,
            'name' => '元旦假期-第2天',
            'holiday_date' => '2026-01-02',
            'repeat_date' => 'No',
            '_who_added' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $holidays[] = [
            'id' => $id++,
            'name' => '元旦假期-第3天',
            'holiday_date' => '2026-01-03',
            'repeat_date' => 'No',
            '_who_added' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // ========== 春节 ==========
        // 2026年春节：农历正月初一为1月27日
        // 放假安排：1月26日（除夕）-2月1日（初六），共7天
        $springFestivalDays = [
            ['date' => '2026-01-26', 'name' => '春节-除夕'],
            ['date' => '2026-01-27', 'name' => '春节-正月初一'],
            ['date' => '2026-01-28', 'name' => '春节-正月初二'],
            ['date' => '2026-01-29', 'name' => '春节-正月初三'],
            ['date' => '2026-01-30', 'name' => '春节-正月初四'],
            ['date' => '2026-01-31', 'name' => '春节-正月初五'],
            ['date' => '2026-02-01', 'name' => '春节-正月初六'],
        ];
        foreach ($springFestivalDays as $day) {
            $holidays[] = [
                'id' => $id++,
                'name' => $day['name'],
                'holiday_date' => $day['date'],
                'repeat_date' => 'No',
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // ========== 清明节 ==========
        // 2026年4月4日-4月6日（3天）
        $holidays[] = [
            'id' => $id++,
            'name' => '清明节',
            'holiday_date' => '2026-04-04',
            'repeat_date' => 'No',
            '_who_added' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $holidays[] = [
            'id' => $id++,
            'name' => '清明节假期-第2天',
            'holiday_date' => '2026-04-05',
            'repeat_date' => 'No',
            '_who_added' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $holidays[] = [
            'id' => $id++,
            'name' => '清明节假期-第3天',
            'holiday_date' => '2026-04-06',
            'repeat_date' => 'No',
            '_who_added' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // ========== 劳动节 ==========
        // 2026年5月1日-5月5日（5天）
        $holidays[] = [
            'id' => $id++,
            'name' => '劳动节',
            'holiday_date' => '2026-05-01',
            'repeat_date' => 'No',
            '_who_added' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $holidays[] = [
            'id' => $id++,
            'name' => '劳动节假期-第2天',
            'holiday_date' => '2026-05-02',
            'repeat_date' => 'No',
            '_who_added' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $holidays[] = [
            'id' => $id++,
            'name' => '劳动节假期-第3天',
            'holiday_date' => '2026-05-03',
            'repeat_date' => 'No',
            '_who_added' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $holidays[] = [
            'id' => $id++,
            'name' => '劳动节假期-第4天',
            'holiday_date' => '2026-05-04',
            'repeat_date' => 'No',
            '_who_added' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $holidays[] = [
            'id' => $id++,
            'name' => '劳动节假期-第5天',
            'holiday_date' => '2026-05-05',
            'repeat_date' => 'No',
            '_who_added' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // ========== 端午节 ==========
        // 2026年端午节：农历五月初五为5月31日
        // 放假安排：5月30日-6月1日（3天）
        $holidays[] = [
            'id' => $id++,
            'name' => '端午节假期-第1天',
            'holiday_date' => '2026-05-30',
            'repeat_date' => 'No',
            '_who_added' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $holidays[] = [
            'id' => $id++,
            'name' => '端午节',
            'holiday_date' => '2026-05-31',
            'repeat_date' => 'No',
            '_who_added' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $holidays[] = [
            'id' => $id++,
            'name' => '端午节假期-第3天',
            'holiday_date' => '2026-06-01',
            'repeat_date' => 'No',
            '_who_added' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // ========== 中秋节 + 国庆节 ==========
        // 2026年中秋节：农历八月十五为10月4日
        // 国庆节与中秋节连休：10月1日-10月8日（8天）
        $nationalDays = [
            ['date' => '2026-10-01', 'name' => '国庆节'],
            ['date' => '2026-10-02', 'name' => '国庆节假期-第2天'],
            ['date' => '2026-10-03', 'name' => '国庆节假期-第3天'],
            ['date' => '2026-10-04', 'name' => '中秋节'],
            ['date' => '2026-10-05', 'name' => '国庆中秋假期-第5天'],
            ['date' => '2026-10-06', 'name' => '国庆中秋假期-第6天'],
            ['date' => '2026-10-07', 'name' => '国庆中秋假期-第7天'],
            ['date' => '2026-10-08', 'name' => '国庆中秋假期-第8天'],
        ];
        foreach ($nationalDays as $day) {
            $holidays[] = [
                'id' => $id++,
                'name' => $day['name'],
                'holiday_date' => $day['date'],
                'repeat_date' => 'No',
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // 插入所有节假日数据
        DB::table('holidays')->insert($holidays);

        $totalDays = count($holidays);
        $this->command->info("已创建 2026 年中国法定节假日数据，共 {$totalDays} 天");
        $this->command->info('节假日安排：');
        $this->command->info('  - 元旦：1月1日-1月3日（3天）');
        $this->command->info('  - 春节：1月26日-2月1日（7天）');
        $this->command->info('  - 清明节：4月4日-4月6日（3天）');
        $this->command->info('  - 劳动节：5月1日-5月5日（5天）');
        $this->command->info('  - 端午节：5月30日-6月1日（3天）');
        $this->command->info('  - 中秋节+国庆节：10月1日-10月8日（8天）');
    }
}
