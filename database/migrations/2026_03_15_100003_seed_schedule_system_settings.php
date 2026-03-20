<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            [
                'key'         => 'schedule.require_schedule_for_booking',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'schedule',
                'description' => '无排班时是否禁止预约（0=使用默认营业时间，1=禁止预约）',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'key'         => 'schedule.max_patients_upper_limit',
                'value'       => '50',
                'type'        => 'integer',
                'group'       => 'schedule',
                'description' => '班次最大接诊数上限',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'key'         => 'schedule.copy_max_range_months',
                'value'       => '3',
                'type'        => 'integer',
                'group'       => 'schedule',
                'description' => '复制排班最大跨度（月）',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', [
            'schedule.require_schedule_for_booking',
            'schedule.max_patients_upper_limit',
            'schedule.copy_max_range_months',
        ])->delete();
    }
};
