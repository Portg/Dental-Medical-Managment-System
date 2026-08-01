<?php

namespace Tests\Feature;

use App\Branch;
use App\DoctorSchedule;
use App\Role;
use App\Services\DoctorScheduleService;
use App\Shift;
use App\SystemSetting;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 排班复制跨度限制（schedule.copy_max_range_months）。
 *
 * 该配置此前只有 seed，业务层从未读取——改了也不生效。
 * 按 AG-020，业务阈值必须走系统设置而非硬编码。
 */
class ScheduleCopyRangeTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;
    private Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Test Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Doctor', 'slug' => 'doctor']);

        $this->doctor = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $branch->id,
            'is_doctor' => 1,
            'status'    => User::STATUS_ACTIVE,
        ]);

        $this->shift = Shift::create([
            'name'         => '上午班',
            'start_time'   => '08:00',
            'end_time'     => '12:00',
            'max_patients' => 10,
            'work_status'  => 'on_duty',
            'is_active'    => 1,
            '_who_added'   => $this->doctor->id,
        ]);

        // 源周：本周一
        DoctorSchedule::create([
            'doctor_id'     => $this->doctor->id,
            'shift_id'      => $this->shift->id,
            'schedule_date' => now()->startOfWeek()->format('Y-m-d'),
            '_who_added'    => $this->doctor->id,
        ]);

        // copyWeek 写入的 _who_added 取自 Auth::id()，没有登录态会因非空约束失败
        $this->actingAs($this->doctor);
    }

    private function service(): DoctorScheduleService
    {
        return app(DoctorScheduleService::class);
    }

    public function test_copy_within_limit_succeeds(): void
    {
        SystemSetting::updateOrCreate(
            ['key' => 'schedule.copy_max_range_months'],
            ['value' => '3', 'type' => 'integer', 'group' => 'schedule']
        );
        SystemSetting::clearCache();

        $result = $this->service()->copyWeek(
            now()->startOfWeek()->format('Y-m-d'),
            now()->startOfWeek()->addWeeks(2)->format('Y-m-d')
        );

        $this->assertTrue($result['success'], $result['message'] ?? '');
    }

    public function test_copy_beyond_limit_is_rejected(): void
    {
        SystemSetting::updateOrCreate(
            ['key' => 'schedule.copy_max_range_months'],
            ['value' => '3', 'type' => 'integer', 'group' => 'schedule']
        );
        SystemSetting::clearCache();

        // 目标周在 6 个月后，超过 3 个月上限
        $result = $this->service()->copyWeek(
            now()->startOfWeek()->format('Y-m-d'),
            now()->startOfWeek()->addMonths(6)->format('Y-m-d')
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('3', $result['message']);
    }

    public function test_limit_is_read_from_system_setting_not_hardcoded(): void
    {
        // 把上限调到 12 个月后，原先被拒的跨度应当放行——
        // 证明阈值确实来自系统设置（AG-020）
        SystemSetting::updateOrCreate(
            ['key' => 'schedule.copy_max_range_months'],
            ['value' => '12', 'type' => 'integer', 'group' => 'schedule']
        );
        SystemSetting::clearCache();

        $result = $this->service()->copyWeek(
            now()->startOfWeek()->format('Y-m-d'),
            now()->startOfWeek()->addMonths(6)->format('Y-m-d')
        );

        $this->assertTrue($result['success'], $result['message'] ?? '');
    }

    public function test_copying_backwards_beyond_limit_is_also_rejected(): void
    {
        // Carbon 3 的 diffInMonths 带符号，向前复制会得到负数；
        // 若不取绝对值，「往前复制半年」会绕过上限
        SystemSetting::updateOrCreate(
            ['key' => 'schedule.copy_max_range_months'],
            ['value' => '3', 'type' => 'integer', 'group' => 'schedule']
        );
        SystemSetting::clearCache();

        $result = $this->service()->copyWeek(
            now()->startOfWeek()->format('Y-m-d'),
            now()->startOfWeek()->subMonths(6)->format('Y-m-d')
        );

        $this->assertFalse($result['success'], '向前超限复制也必须被拒绝');
    }

    public function test_zero_means_no_limit(): void
    {
        SystemSetting::updateOrCreate(
            ['key' => 'schedule.copy_max_range_months'],
            ['value' => '0', 'type' => 'integer', 'group' => 'schedule']
        );
        SystemSetting::clearCache();

        $result = $this->service()->copyWeek(
            now()->startOfWeek()->format('Y-m-d'),
            now()->startOfWeek()->addMonths(24)->format('Y-m-d')
        );

        $this->assertTrue($result['success'], $result['message'] ?? '');
    }
}
