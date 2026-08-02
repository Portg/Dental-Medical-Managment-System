<?php

namespace Database\Seeders;

use App\Shift;
use App\User;
use Illuminate\Database\Seeder;

/**
 * 默认班次。
 *
 * 这批数据原本写在 2026_03_15_100001_create_shifts_table 迁移里，但那段有
 * `if ($adminId === null) return;` —— 迁移跑在 seeder 之前，全新安装时 users
 * 还是空表，于是默认班次被静默跳过，装完 shifts 一条都没有。
 *
 * 后果不是"少点数据"：排班完全依赖 Shift（doctor_schedules.shift_id），
 * 没有班次就排不了班、医生也出不了可预约时段，等于开箱不可用。
 *
 * 挪到 seeder 里，跑在 UsersTableSeeder 之后，_who_added 的外键才有得填。
 * 按 name 幂等，重复执行不会产生重复班次，也不会覆盖管理员改过的时间。
 */
class ShiftsTableSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::query()->min('id');

        if ($adminId === null) {
            $this->command?->warn('ShiftsTableSeeder: users 表为空，跳过（请先执行 UsersTableSeeder）');
            return;
        }

        $shifts = [
            ['name' => '上午班', 'start_time' => '08:00', 'end_time' => '12:00', 'work_status' => Shift::STATUS_ON_DUTY, 'color' => '#F56C6C', 'sort_order' => 1, 'max_patients' => 8],
            ['name' => '下午班', 'start_time' => '13:30', 'end_time' => '18:00', 'work_status' => Shift::STATUS_ON_DUTY, 'color' => '#409EFF', 'sort_order' => 2, 'max_patients' => 8],
            ['name' => '全天班', 'start_time' => '08:00', 'end_time' => '18:00', 'work_status' => Shift::STATUS_ON_DUTY, 'color' => '#67C23A', 'sort_order' => 3, 'max_patients' => 15],
            // 休息班：work_status=rest 不产生可预约时段（AG-038）
            ['name' => '休息',   'start_time' => '00:00', 'end_time' => '00:00', 'work_status' => Shift::STATUS_REST,    'color' => '#909399', 'sort_order' => 4, 'max_patients' => 0],
        ];

        $created = 0;
        foreach ($shifts as $shift) {
            $exists = Shift::withTrashed()->where('name', $shift['name'])->exists();
            if ($exists) {
                continue;
            }

            Shift::create($shift + ['_who_added' => $adminId]);
            $created++;
        }

        $this->command?->info("班次: 新增 {$created} 个（已存在的保持不变）");
    }
}
