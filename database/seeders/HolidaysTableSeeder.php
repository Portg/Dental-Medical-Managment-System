<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HolidaysTableSeeder extends Seeder
{
    /**
     * 运行数据库种子
     * 填充2026年中国法定节假日数据
     *
     * 依据：国务院办公厅关于2026年部分节假日安排的通知（2025年11月4日发布）
     *
     * - 元旦：1月1日-1月3日（3天）
     * - 春节：2月15日（腊月廿八）-2月23日（正月初七）（9天）
     * - 清明节：4月4日-4月6日（3天）
     * - 劳动节：5月1日-5月5日（5天）
     * - 端午节：6月19日-6月21日（3天）
     * - 中秋节：9月25日-9月27日（3天）
     * - 国庆节：10月1日-10月7日（7天）
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('holidays')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $adminId = DB::table('users')->where('role_id', 1)->value('id') ?? 1;

        $holidays = [];
        $id = 1;

        // ========== 元旦 ==========
        // 1月1日（周四）至3日（周六），共3天。1月4日（周日）上班
        $newYearDays = [
            ['date' => '2026-01-01', 'name' => '元旦'],
            ['date' => '2026-01-02', 'name' => '元旦假期-第2天'],
            ['date' => '2026-01-03', 'name' => '元旦假期-第3天'],
        ];
        foreach ($newYearDays as $day) {
            $holidays[] = [
                'id' => $id++,
                'name' => $day['name'],
                'holiday_date' => $day['date'],
                'repeat_date' => false,
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // ========== 春节 ==========
        // 2月15日（腊月廿八）至23日（正月初七），共9天
        // 2月14日（周六）、2月28日（周六）上班
        $springFestivalDays = [
            ['date' => '2026-02-15', 'name' => '春节-腊月廿八'],
            ['date' => '2026-02-16', 'name' => '春节-腊月廿九'],
            ['date' => '2026-02-17', 'name' => '春节-除夕'],
            ['date' => '2026-02-18', 'name' => '春节-正月初一'],
            ['date' => '2026-02-19', 'name' => '春节-正月初二'],
            ['date' => '2026-02-20', 'name' => '春节-正月初三'],
            ['date' => '2026-02-21', 'name' => '春节-正月初四'],
            ['date' => '2026-02-22', 'name' => '春节-正月初五'],
            ['date' => '2026-02-23', 'name' => '春节-正月初六'],
        ];
        foreach ($springFestivalDays as $day) {
            $holidays[] = [
                'id' => $id++,
                'name' => $day['name'],
                'holiday_date' => $day['date'],
                'repeat_date' => false,
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // ========== 清明节 ==========
        // 4月4日（周六）至6日（周一），共3天。不调休
        $qingmingDays = [
            ['date' => '2026-04-04', 'name' => '清明节'],
            ['date' => '2026-04-05', 'name' => '清明节假期-第2天'],
            ['date' => '2026-04-06', 'name' => '清明节假期-第3天'],
        ];
        foreach ($qingmingDays as $day) {
            $holidays[] = [
                'id' => $id++,
                'name' => $day['name'],
                'holiday_date' => $day['date'],
                'repeat_date' => false,
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // ========== 劳动节 ==========
        // 5月1日（周五）至5日（周二），共5天。5月9日（周六）上班
        $laborDays = [
            ['date' => '2026-05-01', 'name' => '劳动节'],
            ['date' => '2026-05-02', 'name' => '劳动节假期-第2天'],
            ['date' => '2026-05-03', 'name' => '劳动节假期-第3天'],
            ['date' => '2026-05-04', 'name' => '劳动节假期-第4天'],
            ['date' => '2026-05-05', 'name' => '劳动节假期-第5天'],
        ];
        foreach ($laborDays as $day) {
            $holidays[] = [
                'id' => $id++,
                'name' => $day['name'],
                'holiday_date' => $day['date'],
                'repeat_date' => false,
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // ========== 端午节 ==========
        // 6月19日（周五）至21日（周日），共3天。不调休
        $dragonBoatDays = [
            ['date' => '2026-06-19', 'name' => '端午节'],
            ['date' => '2026-06-20', 'name' => '端午节假期-第2天'],
            ['date' => '2026-06-21', 'name' => '端午节假期-第3天'],
        ];
        foreach ($dragonBoatDays as $day) {
            $holidays[] = [
                'id' => $id++,
                'name' => $day['name'],
                'holiday_date' => $day['date'],
                'repeat_date' => false,
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // ========== 中秋节 ==========
        // 9月25日（周五）至27日（周日），共3天。不调休
        $midAutumnDays = [
            ['date' => '2026-09-25', 'name' => '中秋节'],
            ['date' => '2026-09-26', 'name' => '中秋节假期-第2天'],
            ['date' => '2026-09-27', 'name' => '中秋节假期-第3天'],
        ];
        foreach ($midAutumnDays as $day) {
            $holidays[] = [
                'id' => $id++,
                'name' => $day['name'],
                'holiday_date' => $day['date'],
                'repeat_date' => false,
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // ========== 国庆节 ==========
        // 10月1日（周四）至7日（周三），共7天。9月20日（周日）、10月10日（周六）上班
        $nationalDays = [
            ['date' => '2026-10-01', 'name' => '国庆节'],
            ['date' => '2026-10-02', 'name' => '国庆节假期-第2天'],
            ['date' => '2026-10-03', 'name' => '国庆节假期-第3天'],
            ['date' => '2026-10-04', 'name' => '国庆节假期-第4天'],
            ['date' => '2026-10-05', 'name' => '国庆节假期-第5天'],
            ['date' => '2026-10-06', 'name' => '国庆节假期-第6天'],
            ['date' => '2026-10-07', 'name' => '国庆节假期-第7天'],
        ];
        foreach ($nationalDays as $day) {
            $holidays[] = [
                'id' => $id++,
                'name' => $day['name'],
                'holiday_date' => $day['date'],
                'repeat_date' => false,
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('holidays')->insert($holidays);

        $totalDays = count($holidays);
        $this->command->info("已创建 2026 年中国法定节假日数据，共 {$totalDays} 天");
        $this->command->info('节假日安排（依据国务院办公厅2025年11月4日通知）：');
        $this->command->info('  - 元旦：1月1日-1月3日（3天）');
        $this->command->info('  - 春节：2月15日-2月23日（9天，史上最长）');
        $this->command->info('  - 清明节：4月4日-4月6日（3天，不调休）');
        $this->command->info('  - 劳动节：5月1日-5月5日（5天）');
        $this->command->info('  - 端午节：6月19日-6月21日（3天，不调休）');
        $this->command->info('  - 中秋节：9月25日-9月27日（3天，不调休）');
        $this->command->info('  - 国庆节：10月1日-10月7日（7天）');
    }
}
