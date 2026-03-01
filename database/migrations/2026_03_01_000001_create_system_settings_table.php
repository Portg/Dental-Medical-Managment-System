<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');
            $table->string('group', 50)->default('general')->index();
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        $now = now();

        // ── 1. Migrate existing member_settings ─────────────────────
        if (Schema::hasTable('member_settings')) {
            $rows = DB::table('member_settings')->get();
            foreach ($rows as $row) {
                DB::table('system_settings')->insert([
                    'key'         => 'member.' . $row->key,
                    'value'       => $row->value,
                    'type'        => $row->type,
                    'group'       => 'member',
                    'description' => $row->description,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }

        // ── 2. Clinic / appointment settings ────────────────────────
        DB::table('system_settings')->insert([
            ['key' => 'clinic.start_time',               'value' => '08:30',  'type' => 'string',  'group' => 'clinic', 'description' => '诊所预约起始时间',                  'created_at' => $now, 'updated_at' => $now],
            ['key' => 'clinic.end_time',                 'value' => '18:30',  'type' => 'string',  'group' => 'clinic', 'description' => '诊所预约结束时间',                  'created_at' => $now, 'updated_at' => $now],
            ['key' => 'clinic.slot_interval',            'value' => '30',     'type' => 'integer', 'group' => 'clinic', 'description' => '预约时段间隔（分钟）',               'created_at' => $now, 'updated_at' => $now],
            ['key' => 'clinic.default_duration',         'value' => '30',     'type' => 'integer', 'group' => 'clinic', 'description' => '默认预约时长（分钟）',               'created_at' => $now, 'updated_at' => $now],
            ['key' => 'clinic.grid_start_hour',          'value' => '8',      'type' => 'integer', 'group' => 'clinic', 'description' => '资源网格显示起始小时',               'created_at' => $now, 'updated_at' => $now],
            ['key' => 'clinic.grid_end_hour',            'value' => '21',     'type' => 'integer', 'group' => 'clinic', 'description' => '资源网格显示结束小时',               'created_at' => $now, 'updated_at' => $now],
            ['key' => 'clinic.hide_off_duty_doctors',    'value' => '0',      'type' => 'boolean', 'group' => 'clinic', 'description' => '预约中心不显示休息医生',              'created_at' => $now, 'updated_at' => $now],
            ['key' => 'clinic.show_appointment_notes',   'value' => '1',      'type' => 'boolean', 'group' => 'clinic', 'description' => '日历/列表显示预约备注',               'created_at' => $now, 'updated_at' => $now],
            ['key' => 'clinic.allow_overbooking',        'value' => '0',      'type' => 'boolean', 'group' => 'clinic', 'description' => '允许同一时段重复预约',                'created_at' => $now, 'updated_at' => $now],
            ['key' => 'clinic.max_advance_days',         'value' => '90',     'type' => 'integer', 'group' => 'clinic', 'description' => '最大提前预约天数（0=不限制）',         'created_at' => $now, 'updated_at' => $now],
            ['key' => 'clinic.min_advance_hours',        'value' => '0',      'type' => 'integer', 'group' => 'clinic', 'description' => '最少提前预约小时数（0=不限制）',       'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
