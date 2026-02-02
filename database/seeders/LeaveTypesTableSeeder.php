<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveTypesTableSeeder extends Seeder
{
    /**
     * 运行数据库种子
     * 填充中国常用休假类型数据
     *
     * @return void
     */
    public function run()
    {
        // 禁用外键检查以避免truncate问题
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('leave_types')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 获取第一个管理员用户ID作为_who_added
        $adminId = DB::table('users')->where('role_id', 1)->value('id') ?? 1;

        // 中国常用休假类型及法定天数
        $leaveTypes = [
            [
                'id' => 1,
                'name' => '年假',
                'max_days' => 15,  // 根据工龄1-15天不等，这里取最大值
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => '病假',
                'max_days' => 180,  // 医疗期最长可达24个月，这里设置半年
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => '产假',
                'max_days' => 158,  // 基础98天 + 各地奖励假期
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => '陪产假',
                'max_days' => 15,  // 各地不同，一般7-30天
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'name' => '事假',
                'max_days' => 30,  // 企业自行规定
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'name' => '调休',
                'max_days' => 30,  // 加班调休
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'name' => '婚假',
                'max_days' => 30,  // 法定3天 + 晚婚假（各地不同）
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 8,
                'name' => '丧假',
                'max_days' => 3,  // 直系亲属1-3天
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 9,
                'name' => '育儿假',
                'max_days' => 10,  // 各地不同，一般5-15天/年
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 10,
                'name' => '护理假',
                'max_days' => 20,  // 独生子女父母住院护理假
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 11,
                'name' => '哺乳假',
                'max_days' => 180,  // 每天1小时哺乳时间，持续到婴儿满一周岁
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 12,
                'name' => '工伤假',
                'max_days' => 365,  // 停工留薪期一般不超过12个月
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('leave_types')->insert($leaveTypes);

        $this->command->info('已创建 ' . count($leaveTypes) . ' 种休假类型：');
        foreach ($leaveTypes as $type) {
            $this->command->info("  - {$type['name']}（最多 {$type['max_days']} 天）");
        }
    }
}
