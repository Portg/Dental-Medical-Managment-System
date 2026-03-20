<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed dict_items with core enum types (code stored in lowercase).
     */
    public function up(): void
    {
        $now = now();
        $rows = [];

        // appointment_status
        $statuses = [
            ['waiting', '待诊', 1],
            ['scheduled', '已预约', 2],
            ['checked_in', '已签到', 3],
            ['in_progress', '诊疗中', 4],
            ['completed', '已完成', 5],
            ['treatment complete', '治疗完成', 6],
            ['treatment incomplete', '治疗未完成', 7],
            ['cancelled', '已取消', 8],
            ['no_show', '爽约', 9],
            ['rescheduled', '已改期', 10],
            ['rejected', '已拒绝', 11],
        ];
        foreach ($statuses as $i => $s) {
            $rows[] = ['type' => 'appointment_status', 'code' => $s[0], 'name' => $s[1], 'sort_order' => $s[2], 'created_at' => $now, 'updated_at' => $now];
        }

        // appointment_visit_information
        $visits = [
            ['walk_in', '现场挂号', 1],
            ['appointment', '预约', 2],
            ['single treatment', '单次治疗', 3],
            ['review treatment', '复诊', 4],
        ];
        foreach ($visits as $v) {
            $rows[] = ['type' => 'appointment_visit_information', 'code' => $v[0], 'name' => $v[1], 'sort_order' => $v[2], 'created_at' => $now, 'updated_at' => $now];
        }

        // appointment_type
        $aptTypes = [
            ['first_visit', '初诊', 1],
            ['revisit', '复诊', 2],
            ['follow_up', '随访', 3],
            ['emergency', '急诊', 4],
            ['consultation', '咨询', 5],
        ];
        foreach ($aptTypes as $t) {
            $rows[] = ['type' => 'appointment_type', 'code' => $t[0], 'name' => $t[1], 'sort_order' => $t[2], 'created_at' => $now, 'updated_at' => $now];
        }

        // invoice_payment_status
        $payments = [
            ['unpaid', '未付款', 1],
            ['partial', '部分付款', 2],
            ['paid', '已付清', 3],
            ['refunded', '已退款', 4],
            ['overdue', '逾期', 5],
            ['written_off', '核销', 6],
        ];
        foreach ($payments as $p) {
            $rows[] = ['type' => 'invoice_payment_status', 'code' => $p[0], 'name' => $p[1], 'sort_order' => $p[2], 'created_at' => $now, 'updated_at' => $now];
        }

        // invoice_discount_status
        $discounts = [
            ['none', '无', 1],
            ['pending', '待审批', 2],
            ['approved', '已通过', 3],
            ['rejected', '已拒绝', 4],
        ];
        foreach ($discounts as $d) {
            $rows[] = ['type' => 'invoice_discount_status', 'code' => $d[0], 'name' => $d[1], 'sort_order' => $d[2], 'created_at' => $now, 'updated_at' => $now];
        }

        // waiting_queue_status
        $queueStatuses = [
            ['waiting', '候诊', 1],
            ['called', '已叫号', 2],
            ['in_treatment', '诊疗中', 3],
            ['completed', '已完成', 4],
            ['cancelled', '已取消', 5],
            ['no_show', '爽约', 6],
        ];
        foreach ($queueStatuses as $q) {
            $rows[] = ['type' => 'waiting_queue_status', 'code' => $q[0], 'name' => $q[1], 'sort_order' => $q[2], 'created_at' => $now, 'updated_at' => $now];
        }

        // medical_case_status
        $caseStatuses = [
            ['open', '进行中', 1],
            ['closed', '已关闭', 2],
            ['follow-up', '随访', 3],
        ];
        foreach ($caseStatuses as $c) {
            $rows[] = ['type' => 'medical_case_status', 'code' => $c[0], 'name' => $c[1], 'sort_order' => $c[2], 'created_at' => $now, 'updated_at' => $now];
        }

        // patient_status
        $patientStatuses = [
            ['active', '正常', 1],
            ['merged', '已合并', 2],
            ['archived', '已归档', 3],
        ];
        foreach ($patientStatuses as $ps) {
            $rows[] = ['type' => 'patient_status', 'code' => $ps[0], 'name' => $ps[1], 'sort_order' => $ps[2], 'created_at' => $now, 'updated_at' => $now];
        }

        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('dict_items')->insert($chunk);
        }
    }

    public function down(): void
    {
        $types = [
            'appointment_status',
            'appointment_visit_information',
            'appointment_type',
            'invoice_payment_status',
            'invoice_discount_status',
            'waiting_queue_status',
            'medical_case_status',
            'patient_status',
        ];
        DB::table('dict_items')->whereIn('type', $types)->delete();
    }
};
