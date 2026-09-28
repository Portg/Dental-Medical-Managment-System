<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\Chair;
use App\Patient;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\Services\AppointmentService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 诊室泳道视图 + 预约状态筛选。
 *
 * 椅位是牙科的产能单位，没有诊室列就排不了椅位。视图与医生泳道共用同一份
 * ResourceGrid（mode='chair'），后端要保证两件事：
 *   - 事件里带 chair_id，否则每个事件都落不进任何一列
 *   - 诊室列的形状与医生列一致（{id, title}），因为渲染的是同一段代码
 *
 * 状态筛选做在客户端（三个视图共用一份），后端只负责供给色卡 —— 但色卡必须
 * 与事件底色同源，否则图例和事件对不上，而这个面板全部作用就是按颜色认状态。
 */
class AppointmentChairLaneTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $doctor;
    private Patient $patient;
    private Branch $branch;
    private int $aptSeq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::first() ?: Branch::create(['name' => 'Main Branch', 'is_active' => true]);

        $perm = Permission::firstOrCreate(
            ['slug' => 'view-appointments'],
            ['name' => 'view-appointments', 'module' => '预约管理']
        );
        $role = Role::create(['name' => 'Front Desk', 'slug' => 'front-desk-chairs']);
        RolePermission::create(['role_id' => $role->id, 'permission_id' => $perm->id]);

        $this->staff = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $this->branch->id, 'status' => User::STATUS_ACTIVE,
        ]);
        $this->doctor = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $this->branch->id,
            'is_doctor' => true, 'status' => User::STATUS_ACTIVE,
        ]);

        $this->patient = Patient::create([
            'patient_no' => 'CH-' . uniqid(),
            'surname'    => '翟',
            'othername'  => '登云',
            'phone_no'   => '18747484723',
            '_who_added' => $this->staff->id,
        ]);
    }

    private function makeAppointment(?int $chairId, string $status = 'scheduled'): Appointment
    {
        $date = date('Y-m-d');

        return Appointment::create([
            // 预约号自己发：AppointmentNo() 同秒连发会撞唯一键（既有实现的问题）
            'appointment_no' => 910000 + (++$this->aptSeq),
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctor->id,
            'start_date'     => $date,
            'end_date'       => $date,
            'start_time'     => '10:00:00',
            'sort_by'        => $date . ' 10:00:00',
            'branch_id'      => $this->branch->id,
            'chair_id'       => $chairId,
            'status'         => $status,
            '_who_added'     => $this->staff->id,
        ]);
    }

    private function events(): array
    {
        $date = date('Y-m-d');
        $next = date('Y-m-d', strtotime('+1 day'));

        return app(AppointmentService::class)->getCalendarEvents($date, $next);
    }

    /** @test */
    public function 日历事件带上诊室信息(): void
    {
        $chair = Chair::create([
            'chair_name' => '一诊室', 'chair_code' => 'C1', 'branch_id' => $this->branch->id,
            'status' => 'active', '_who_added' => $this->staff->id,
        ]);
        $this->makeAppointment($chair->id);

        $events = $this->events();

        $this->assertCount(1, $events);
        $this->assertSame($chair->id, $events[0]['extendedProps']['chair_id']);
        $this->assertSame('一诊室', $events[0]['extendedProps']['chair_name']);
    }

    /**
     * 没排椅位的预约不能从泳道视图里消失 —— 牙科排椅位常常是当天才定的，
     * 看不见就等于今天这些人不存在。前端把 chair_id 为空的归进 id 0 那一列。
     */
    /** @test */
    public function 没排椅位的预约chair_id为空而不是被丢掉(): void
    {
        $this->makeAppointment(null);

        $events = $this->events();

        $this->assertCount(1, $events);
        $this->assertArrayHasKey('chair_id', $events[0]['extendedProps']);
        $this->assertNull($events[0]['extendedProps']['chair_id']);
    }

    /** @test */
    public function 诊室列的形状与医生列一致并在末尾补未分配那一列(): void
    {
        // chair_code 有唯一索引，两把椅子不能都留空
        Chair::create([
            'chair_name' => '一诊室', 'chair_code' => 'C1', 'branch_id' => $this->branch->id,
            'status' => 'active', '_who_added' => $this->staff->id,
        ]);
        Chair::create([
            'chair_name' => '二诊室', 'chair_code' => 'C2', 'branch_id' => $this->branch->id,
            'status' => 'active', '_who_added' => $this->staff->id,
        ]);

        $rows = $this->actingAs($this->staff)
            ->getJson('/appointments/chair-resources')
            ->assertOk()
            ->json();

        $this->assertCount(3, $rows);
        // 与 /appointments/doctors 一样是 {id, title}：渲染的是同一段代码
        foreach ($rows as $row) {
            $this->assertArrayHasKey('id', $row);
            $this->assertArrayHasKey('title', $row);
        }
        $this->assertSame('一诊室', $rows[0]['title']);
        // 「未分配诊室」永远在最后一列，id 用 0
        $this->assertSame(0, $rows[2]['id']);
        $this->assertSame(__('appointment.chair_unassigned'), $rows[2]['title']);
    }

    /** @test */
    public function 状态色卡与事件底色同源(): void
    {
        $this->makeAppointment(null, Appointment::STATUS_CANCELLED);

        $events = $this->events();
        $statuses = collect(app(AppointmentService::class)->filterableStatuses())
            ->keyBy('code');

        $this->assertSame(
            $statuses[Appointment::STATUS_CANCELLED]['color'],
            $events[0]['backgroundColor'],
            '筛选面板的色卡和事件底色对不上，图例就没有意义了'
        );
    }

    /** @test */
    public function 状态筛选项带上状态码颜色与译名(): void
    {
        $rows = app(AppointmentService::class)->filterableStatuses();

        $this->assertNotEmpty($rows);
        foreach ($rows as $row) {
            $this->assertArrayHasKey('code', $row);
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $row['color']);
            $this->assertNotSame('', $row['label']);
        }

        // 旧枚举值不进筛选面板：库里还有数据，但摆出来只会让人以为是
        // 两种不同的「完成」
        $codes = array_column($rows, 'code');
        $this->assertNotContains(Appointment::STATUS_TREATMENT_COMPLETE, $codes);
        $this->assertContains(Appointment::STATUS_COMPLETED, $codes);
    }

    /** @test */
    public function 预约页上有诊室页签与状态筛选面板(): void
    {
        $html = $this->actingAs($this->staff)->get('/appointments')->assertOk()->getContent();

        $this->assertStringContainsString('chair_day_view_tab', $html);
        $this->assertStringContainsString('crg-container', $html);
        $this->assertStringContainsString('aptStatusFilter', $html);
    }
}
