<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\MedicalService;
use App\Patient;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\Services\AppointmentService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * 一次预约约多个项目。
 *
 * 对齐视频：新建预约右侧是一串可勾选的项目，而我们原先只能选一个 —— 前台
 * 要么只记一个、要么开两条预约。
 *
 * 关键的设计约定（见 appointment_services 建表迁移）：
 *   - appointment_services 是**全集**，appointments.service_id 是它的第一项
 *   - service_id 保留是有意的反规范化，让 8 处既有读取点不改也仍然正确
 *   - 两者的同步只在 AppointmentService 一处发生
 *
 * 这组用例就是钉住这个约定：多选存得进、主项目对得上、老的单选入口照样能用、
 * 不提项目的局部更新不会把项目清空。
 */
class AppointmentMultiServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $doctor;
    private Patient $patient;
    private MedicalService $fill;
    private MedicalService $clean;
    private MedicalService $extract;

    protected function setUp(): void
    {
        parent::setUp();

        // 建预约会 dispatch 短信任务，而 sms_loggings 缺 type 列（既有 bug）
        Bus::fake();

        $branch = Branch::first() ?: Branch::create(['name' => 'Main Branch', 'is_active' => true]);

        $perms = [];
        foreach (['view-appointments', 'create-appointments', 'edit-appointments'] as $slug) {
            $perms[$slug] = Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $slug, 'module' => '预约管理']
            )->id;
        }
        $role = Role::create(['name' => 'Front Desk', 'slug' => 'front-desk-multisvc']);
        foreach ($perms as $permId) {
            RolePermission::create(['role_id' => $role->id, 'permission_id' => $permId]);
        }

        $this->staff = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $branch->id, 'status' => User::STATUS_ACTIVE,
        ]);
        $this->doctor = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $branch->id,
            'is_doctor' => true, 'status' => User::STATUS_ACTIVE,
        ]);

        $this->patient = Patient::create([
            'patient_no' => 'MS-' . uniqid(),
            'surname'    => '孔',
            'othername'  => '庆福',
            'phone_no'   => '18610283919',
            '_who_added' => $this->staff->id,
        ]);

        $this->fill = MedicalService::create([
            'name' => '补牙', 'unit' => '颗', 'price' => 280,
            'is_active' => true, '_who_added' => $this->staff->id,
        ]);
        $this->clean = MedicalService::create([
            'name' => '洁治', 'unit' => '次', 'price' => 200,
            'is_active' => true, '_who_added' => $this->staff->id,
        ]);
        $this->extract = MedicalService::create([
            'name' => '拔牙', 'unit' => '颗', 'price' => 500,
            'is_active' => true, '_who_added' => $this->staff->id,
        ]);

        // createAppointment 内部读 Auth::user()->branch_id：直接调服务层的
        // 用例也得先登录，否则挂在 branch_id 上而不是本用例要验的那件事
        $this->actingAs($this->staff);
    }

    private function bookPayload(array $overrides = []): array
    {
        return array_merge([
            'visit_information' => Appointment::VISIT_WALK_IN,
            'appointment_date'  => date('Y-m-d'),
            'appointment_time'  => '10:00',
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->doctor->id,
            'send_sms'          => '0',
        ], $overrides);
    }

    private function service(): AppointmentService
    {
        return app(AppointmentService::class);
    }

    /** @test */
    public function 多选的项目全部存进透视表(): void
    {
        $this->actingAs($this->staff)
            ->postJson('/appointments', $this->bookPayload([
                'service_ids' => [$this->fill->id, $this->clean->id, $this->extract->id],
            ]))
            ->assertOk()
            ->assertJsonPath('status', true);

        $appointment = Appointment::firstOrFail();

        $this->assertSame(3, $appointment->services()->count());
        $this->assertSame(
            ['补牙', '洁治', '拔牙'],
            $appointment->services->pluck('name')->all(),
            '顺序要按勾选顺序，不是按 id'
        );
    }

    /**
     * service_id 是全集的第一项。这条一旦不成立，8 处只读 service_id 的地方
     * （工作台 6 个列表、API 资源、挂号）显示的就不是这次预约的主项目了。
     */
    /** @test */
    public function 主项目是勾选里的第一个(): void
    {
        $this->actingAs($this->staff)
            ->postJson('/appointments', $this->bookPayload([
                'service_ids' => [$this->clean->id, $this->fill->id],
            ]))
            ->assertOk();

        $appointment = Appointment::firstOrFail();
        $this->assertSame($this->clean->id, $appointment->service_id);
    }

    /** @test */
    public function 老的单选入口照样能用(): void
    {
        // 挂号、API v1 传的都是单个 service_id
        $appointment = $this->service()->createAppointment([
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->doctor->id,
            'appointment_date'  => date('Y-m-d'),
            'appointment_time'  => '11:00',
            'visit_information' => Appointment::VISIT_WALK_IN,
            'service_id'        => $this->fill->id,
            'send_sms'          => '0',
        ]);

        $this->assertSame($this->fill->id, $appointment->service_id);
        // 单选也要进透视表，否则显示全集的地方会漏掉这条
        $this->assertSame(1, $appointment->services()->count());
    }

    /** @test */
    public function 两者都给时以多选为准(): void
    {
        // 抽屉提交时两个字段会同时在：只认一个才不会「界面勾了三个、存进去一个」
        $this->actingAs($this->staff)
            ->postJson('/appointments', $this->bookPayload([
                'service_id'  => $this->extract->id,
                'service_ids' => [$this->fill->id, $this->clean->id],
            ]))
            ->assertOk();

        $appointment = Appointment::firstOrFail();
        $this->assertSame($this->fill->id, $appointment->service_id);
        $this->assertEqualsCanonicalizing(
            [$this->fill->id, $this->clean->id],
            $appointment->services->pluck('id')->all()
        );
    }

    /** @test */
    public function 重复勾同一个项目只存一条(): void
    {
        $this->actingAs($this->staff)
            ->postJson('/appointments', $this->bookPayload([
                'service_ids' => [$this->fill->id, $this->fill->id, $this->clean->id],
            ]))
            ->assertOk();

        $this->assertSame(2, Appointment::firstOrFail()->services()->count());
    }

    /** @test */
    public function 编辑时改勾选会同步到透视表与主项目(): void
    {
        $appointment = $this->service()->createAppointment([
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->doctor->id,
            'appointment_date'  => date('Y-m-d'),
            'appointment_time'  => '11:00',
            'visit_information' => Appointment::VISIT_WALK_IN,
            'service_ids'       => [$this->fill->id, $this->clean->id],
            'send_sms'          => '0',
        ]);

        $this->actingAs($this->staff)
            ->putJson('/appointments/' . $appointment->id, [
                'visit_information' => Appointment::VISIT_WALK_IN,
                'patient_id'        => $this->patient->id,
                'doctor_id'         => $this->doctor->id,
                'appointment_date'  => date('Y-m-d'),
                'appointment_time'  => '11:00',
                'service_ids'       => [$this->extract->id],
            ])
            ->assertOk();

        $appointment->refresh();
        $this->assertSame($this->extract->id, $appointment->service_id);
        $this->assertSame([$this->extract->id], $appointment->services->pluck('id')->all());
    }

    /**
     * 改期、改状态这类局部更新根本不提项目 —— 顺手清空是最隐蔽的一类数据丢失：
     * 前台改了个时间，项目没了，而界面上没有任何提示。
     */
    /** @test */
    public function 不提项目的更新不会清空已选项目(): void
    {
        $appointment = $this->service()->createAppointment([
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->doctor->id,
            'appointment_date'  => date('Y-m-d'),
            'appointment_time'  => '11:00',
            'visit_information' => Appointment::VISIT_WALK_IN,
            'service_ids'       => [$this->fill->id, $this->clean->id],
            'send_sms'          => '0',
        ]);

        $this->service()->updateAppointment($appointment->id, [
            'visit_information' => Appointment::VISIT_WALK_IN,
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->doctor->id,
            'appointment_date'  => date('Y-m-d'),
            'appointment_time'  => '15:00',
        ]);

        $appointment->refresh();
        $this->assertSame(2, $appointment->services()->count());
        $this->assertSame($this->fill->id, $appointment->service_id);
    }

    /** @test */
    public function 编辑回填带上已选项目且保持顺序(): void
    {
        $appointment = $this->service()->createAppointment([
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->doctor->id,
            'appointment_date'  => date('Y-m-d'),
            'appointment_time'  => '11:00',
            'visit_information' => Appointment::VISIT_WALK_IN,
            'service_ids'       => [$this->clean->id, $this->fill->id],
            'send_sms'          => '0',
        ]);

        $data = $this->actingAs($this->staff)
            ->getJson('/appointments/' . $appointment->id . '/edit')
            ->assertOk()
            ->json();

        $this->assertCount(2, $data['services']);
        $this->assertSame('洁治', $data['services'][0]['text']);
        $this->assertSame('补牙', $data['services'][1]['text']);
    }

    /** @test */
    public function 日历事件显示全部项目而不只是主项目(): void
    {
        $this->service()->createAppointment([
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->doctor->id,
            'appointment_date'  => date('Y-m-d'),
            'appointment_time'  => '11:00',
            'visit_information' => Appointment::VISIT_WALK_IN,
            'service_ids'       => [$this->fill->id, $this->clean->id],
            'send_sms'          => '0',
        ]);

        $events = $this->service()->getCalendarEvents(
            date('Y-m-d'), date('Y-m-d', strtotime('+1 day'))
        );

        $this->assertCount(1, $events);
        $this->assertSame('补牙, 洁治', $events[0]['extendedProps']['service_name']);
    }

    /** @test */
    public function 没有透视表记录的老预约退回显示主项目(): void
    {
        // 直接建一条只有 service_id 的预约，模拟本表上线前的数据
        $appointment = Appointment::create([
            'appointment_no'    => 920001,
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->doctor->id,
            'start_date'        => date('Y-m-d'),
            'end_date'          => date('Y-m-d'),
            'start_time'        => '09:00:00',
            'sort_by'           => date('Y-m-d') . ' 09:00:00',
            'service_id'        => $this->extract->id,
            'branch_id'         => $this->staff->branch_id,
            '_who_added'        => $this->staff->id,
        ]);

        $this->assertSame(0, $appointment->services()->count());

        $events = $this->service()->getCalendarEvents(
            date('Y-m-d'), date('Y-m-d', strtotime('+1 day'))
        );

        $this->assertSame('拔牙', $events[0]['extendedProps']['service_name']);
    }

    /** @test */
    public function 不存在的项目id会被拒(): void
    {
        $this->actingAs($this->staff)
            ->postJson('/appointments', $this->bookPayload([
                'service_ids' => [$this->fill->id, 999999],
            ]))
            ->assertStatus(422);

        $this->assertSame(0, Appointment::count());
    }
}
