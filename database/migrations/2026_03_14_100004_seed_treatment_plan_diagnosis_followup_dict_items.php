<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rows = [];

        // treatment_plan_status
        foreach ([
            ['Planned',     '计划中', 1],
            ['In Progress', '进行中', 2],
            ['Completed',   '已完成', 3],
            ['Cancelled',   '已取消', 4],
        ] as $item) {
            $rows[] = ['type' => 'treatment_plan_status', 'code' => $item[0], 'name' => $item[1], 'sort_order' => $item[2], 'created_at' => $now, 'updated_at' => $now];
        }

        // treatment_plan_approval_status
        foreach ([
            ['pending',          '待审批', 1],
            ['approved',         '已通过', 2],
            ['rejected',         '已拒绝', 3],
            ['revision_needed',  '需修改', 4],
        ] as $item) {
            $rows[] = ['type' => 'treatment_plan_approval_status', 'code' => $item[0], 'name' => $item[1], 'sort_order' => $item[2], 'created_at' => $now, 'updated_at' => $now];
        }

        // diagnosis_status
        foreach ([
            ['Active',   '活跃',   1],
            ['Resolved', '已解决', 2],
            ['Chronic',  '慢性',   3],
        ] as $item) {
            $rows[] = ['type' => 'diagnosis_status', 'code' => $item[0], 'name' => $item[1], 'sort_order' => $item[2], 'created_at' => $now, 'updated_at' => $now];
        }

        // patient_followup_status
        foreach ([
            ['Pending',     '待跟进', 1],
            ['Completed',   '已完成', 2],
            ['Cancelled',   '已取消', 3],
            ['No Response', '无回应', 4],
        ] as $item) {
            $rows[] = ['type' => 'patient_followup_status', 'code' => $item[0], 'name' => $item[1], 'sort_order' => $item[2], 'created_at' => $now, 'updated_at' => $now];
        }

        // diagnosis_severity
        foreach ([
            ['Mild',     '轻度', 1],
            ['Moderate', '中度', 2],
            ['Severe',   '重度', 3],
        ] as $item) {
            $rows[] = ['type' => 'diagnosis_severity', 'code' => $item[0], 'name' => $item[1], 'sort_order' => $item[2], 'created_at' => $now, 'updated_at' => $now];
        }

        // patient_followup_type
        foreach ([
            ['Phone', '电话', 1],
            ['SMS',   '短信', 2],
            ['Email', '邮件', 3],
            ['Visit', '上门', 4],
            ['Other', '其他', 5],
        ] as $item) {
            $rows[] = ['type' => 'patient_followup_type', 'code' => $item[0], 'name' => $item[1], 'sort_order' => $item[2], 'created_at' => $now, 'updated_at' => $now];
        }

        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('dict_items')->insert($chunk);
        }
    }

    public function down(): void
    {
        DB::table('dict_items')->whereIn('type', [
            'treatment_plan_status',
            'treatment_plan_approval_status',
            'diagnosis_status',
            'diagnosis_severity',
            'patient_followup_status',
            'patient_followup_type',
        ])->delete();
    }
};
