<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\Patient;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use App\WaitingQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * 挂号 —— 到店患者当场进今天的就诊队列。
 *
 * 参考视频的前台动线是「新增患者 → 挂号 → 写病历」。今日工作列表是从
 * appointments 出的，所以挂号必须真的落一条今天的 walk_in 预约 + 一条候诊记录；
 * 少了任何一半，患者要么不出现在台面上，要么出现了却不在队列里。
 */
class WalkInRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionist;
    private User $noPermStaff;
    private User $doctor;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        // 建预约会 dispatch 短信任务，而 sms_loggings 缺 type 列（既有 bug，
        // 与本组用例无关）。与 FollowupToAppointmentClosureTest 的做法一致。
        Bus::fake();

        $branch = Branch::first() ?: Branch::create(['name' => 'Main Branch', 'is_active' => true]);

        $perms = [];
        // 病历权限一并发：挂号的下一步就是开病历，这条链要能一路走到底
        foreach (['view-appointments', 'create-appointments', 'view-medical-cases', 'manage-medical-cases'] as $slug) {
            $perms[$slug] = Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $slug, 'module' => '预约管理']
            )->id;
        }

        $frontDeskRole = Role::create(['name' => 'Front Desk', 'slug' => 'front-desk']);
        foreach ($perms as $permId) {
            RolePermission::create(['role_id' => $frontDeskRole->id, 'permission_id' => $permId]);
        }

        // 只能看、不能建的角色：挂号会真的建出一条预约，必须挡住
        $viewerRole = Role::create(['name' => 'Viewer', 'slug' => 'viewer']);
        RolePermission::create(['role_id' => $viewerRole->id, 'permission_id' => $perms['view-appointments']]);
        RolePermission::create(['role_id' => $viewerRole->id, 'permission_id' => $perms['view-medical-cases']]);

        $this->receptionist = User::factory()->create([
            'role_id' => $frontDeskRole->id, 'branch_id' => $branch->id, 'status' => User::STATUS_ACTIVE,
        ]);
        $this->noPermStaff = User::factory()->create([
            'role_id' => $viewerRole->id, 'branch_id' => $branch->id, 'status' => User::STATUS_ACTIVE,
        ]);
        $this->doctor = User::factory()->create([
            'role_id' => $frontDeskRole->id, 'branch_id' => $branch->id,
            'is_doctor' => true, 'status' => User::STATUS_ACTIVE,
        ]);

        $this->patient = Patient::create([
            'patient_no' => 'REG-' . uniqid(),
            'surname'    => '刘',
            'othername'  => '万友',
            'gender'     => 'Male',
            'phone_no'   => '13800138001',
            '_who_added' => $this->receptionist->id,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'patient_id'       => $this->patient->id,
            'doctor_id'        => $this->doctor->id,
            'appointment_type' => 'first_visit',
            'notes'            => '新增患者',
        ], $overrides);
    }

    /** @test */
    public function 挂号会建出今天的到店预约并同时进候诊队列(): void
    {
        $response = $this->actingAs($this->receptionist)
            ->postJson('/waiting-queue/register', $this->payload());

        $response->assertOk()->assertJsonPath('status', 'success');

        $appointment = Appointment::where('patient_id', $this->patient->id)->firstOrFail();
        $this->assertSame(Appointment::VISIT_WALK_IN, $appointment->visit_information);
        $this->assertSame(date('Y-m-d'), substr((string) $appointment->start_date, 0, 10));
        $this->assertSame('first_visit', $appointment->appointment_type);
        $this->assertSame('新增患者', $appointment->notes);

        $queue = WaitingQueue::where('appointment_id', $appointment->id)->firstOrFail();
        $this->assertSame(WaitingQueue::STATUS_WAITING, $queue->status);
        $this->assertNotNull($queue->check_in_time);
        // 医生队列与「叫下一位」都按 waiting_queues.doctor_id 过滤，
        // 丢了这个字段，患者进了队列却永远轮不到他
        $this->assertSame($this->doctor->id, (int) $queue->doctor_id);
        $this->assertSame('first_visit', $queue->visit_type);
    }

    /**
     * 挂完号预约必须是「已到院」：今日工作按 waiting_queues 与预约状态一起
     * 算显示状态，停在 waiting 的话患者会显示成「预约未到」——人就站在台前。
     */
    /** @test */
    public function 挂号后预约状态是已到院(): void
    {
        $this->actingAs($this->receptionist)
            ->postJson('/waiting-queue/register', $this->payload())
            ->assertOk();

        $appointment = Appointment::where('patient_id', $this->patient->id)->firstOrFail();
        $this->assertSame(Appointment::STATUS_CHECKED_IN, $appointment->status);
    }

    /** @test */
    public function 挂号后该患者出现在今日工作列表里(): void
    {
        $this->actingAs($this->receptionist)
            ->postJson('/waiting-queue/register', $this->payload())
            ->assertOk();

        $rows = app(\App\Services\TodayWorkService::class)
            ->getTodayWorkQuery($this->receptionist->branch_id, 'all', null)
            ->get();

        $this->assertCount(1, $rows, '挂完号，这个人就该在今日就诊里 —— 列表是从预约出的');
        $this->assertSame($this->patient->id, (int) $rows->first()->patient_id);
    }

    /** @test */
    public function 没有新增预约权限的人不能挂号(): void
    {
        $this->actingAs($this->noPermStaff)
            ->postJson('/waiting-queue/register', $this->payload())
            ->assertForbidden();

        $this->assertSame(0, Appointment::where('patient_id', $this->patient->id)->count());
    }

    /** @test */
    public function 挂号必须带患者与医生(): void
    {
        $this->actingAs($this->receptionist)
            ->postJson('/waiting-queue/register', ['notes' => '缺人缺医生'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['patient_id', 'doctor_id']);
    }

    /**
     * 挂号的下一步是开病历。工作台的「病历」按钮要落到真正的病历页，
     * 并且把这次就诊、接诊医生、就诊类型一起带过去 —— 挂号时刚选过医生，
     * 到病历页再选一遍，选错了病历就挂在别人名下。
     */
    /** @test */
    public function 从工作台开病历会带上这次就诊与挂号医生(): void
    {
        $this->actingAs($this->receptionist)
            ->postJson('/waiting-queue/register', $this->payload(['appointment_type' => 'revisit']))
            ->assertOk();

        $appointment = Appointment::where('patient_id', $this->patient->id)->firstOrFail();

        $html = $this->actingAs($this->receptionist)
            ->get('/medical-case-new/' . $this->patient->id . '?appointment_id=' . $appointment->id)
            ->assertOk()->getContent();

        $this->assertStringContainsString(
            'id="appointment_id"',
            $html,
            '病历表单要有 appointment_id，否则保存出来的病历不知道对应哪次就诊'
        );
        $this->assertStringContainsString('value="' . $appointment->id . '"', $html);
        $this->assertMatchesRegularExpression(
            '/<option value="' . $this->doctor->id . '"\s*selected/',
            $html,
            '接诊医生应当预选成挂号时选的那位'
        );
        $this->assertMatchesRegularExpression(
            '/value="revisit"\s*checked/',
            $html,
            '挂号选了复诊，病历的就诊类型就该是复诊'
        );
    }

    /**
     * 别的患者的就诊 id 不能拿来当自己的：这个参数是从 URL 来的。
     */
    /** @test */
    public function 别人的就诊id不会被带进病历(): void
    {
        $this->actingAs($this->receptionist)
            ->postJson('/waiting-queue/register', $this->payload())
            ->assertOk();
        $appointment = Appointment::where('patient_id', $this->patient->id)->firstOrFail();

        $other = Patient::create([
            'patient_no' => 'REG-' . uniqid(),
            'surname'    => '张',
            'othername'  => '三',
            'gender'     => 'Female',
            'phone_no'   => '13800138002',
            '_who_added' => $this->receptionist->id,
        ]);

        $html = $this->actingAs($this->receptionist)
            ->get('/medical-case-new/' . $other->id . '?appointment_id=' . $appointment->id)
            ->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/<option value="' . $this->doctor->id . '"\s*selected/',
            $html,
            '不是这位患者的就诊，不该把那次就诊的医生预选上'
        );
    }

    /**
     * 入口本身也要在页面上 —— 接口通了但按钮没渲染出来，对前台等于没做。
     */
    /** @test */
    public function 工作台页面上有挂号入口与弹窗(): void
    {
        $html = $this->actingAs($this->receptionist)->get('/today-work')
            ->assertOk()->getContent();

        $this->assertStringContainsString('openRegistrationModal(', $html, '顶部要有挂号按钮');
        $this->assertStringContainsString('id="registration-modal"', $html, '挂号弹窗要被 include 进来');
        $this->assertStringContainsString('include_js/registration_modal.js', $html);
    }

    /**
     * 复诊挂号不能被写成初诊：这个值直接决定病历页默认开初诊还是复诊病历。
     */
    /** @test */
    public function 就诊类型按挂号时选的存(): void
    {
        $this->actingAs($this->receptionist)
            ->postJson('/waiting-queue/register', $this->payload(['appointment_type' => 'revisit']))
            ->assertOk();

        $this->assertSame(
            'revisit',
            Appointment::where('patient_id', $this->patient->id)->firstOrFail()->appointment_type
        );
    }
}
