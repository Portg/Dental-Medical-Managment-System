<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\ClaimRate;
use App\DoctorClaim;
use App\Patient;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 医生自助模块（/doctor-appointments、/claims）的横向越权。
 *
 * 两个控制器构造函数里一条中间件都没有，服务层按 id 取单条的方法也不校验归属：
 *   DoctorAppointmentService::getAppointmentForEdit/updateAppointment/updateStatus/deleteAppointment
 *   DoctorModuleClaimService::getClaimForEdit/updateClaim/deleteClaim
 * 列表和日历一直是按 Auth::User()->id 过滤的，所以从界面上看不出问题 ——
 * 但把 URL 里的 id 换成别人的，读改删全部照做。提成那条还会把 _who_added
 * 改成调用者自己，等于把别人的提成记录据为己有。
 *
 * 另外，列表只给 Pending 的提成显示编辑/删除按钮，接口侧此前没跟上这个规则。
 */
class DoctorSelfServiceOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorA;
    private User $doctorB;
    private User $receptionist;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);

        $doctorRole = Role::create(['name' => 'Doctor', 'slug' => 'doctor']);
        $frontRole  = Role::create(['name' => 'Receptionist', 'slug' => 'receptionist']);

        $viewAppointments = Permission::create([
            'name'   => '查看预约',
            'slug'   => 'view-appointments',
            'module' => '预约管理',
        ]);
        RolePermission::create([
            'role_id'       => $doctorRole->id,
            'permission_id' => $viewAppointments->id,
        ]);

        $this->doctorA = User::factory()->create([
            'role_id'   => $doctorRole->id,
            'branch_id' => $branch->id,
            'is_doctor' => 'yes',
            'password'  => bcrypt('password'),
        ]);

        $this->doctorB = User::factory()->create([
            'role_id'   => $doctorRole->id,
            'branch_id' => $branch->id,
            'is_doctor' => 'yes',
            'password'  => bcrypt('password'),
        ]);

        // 前台没有 view-appointments，用来验证模块级门禁
        $this->receptionist = User::factory()->create([
            'role_id'   => $frontRole->id,
            'branch_id' => $branch->id,
            'password'  => bcrypt('password'),
        ]);

        $this->patient = Patient::create([
            'patient_no' => '20260001',
            'surname'    => '张',
            'othername'  => '三',
            'gender'     => 'Male',
            'phone_no'   => '13800138000',
            '_who_added' => $this->doctorA->id,
        ]);

        Cache::flush();
    }

    private function appointmentFor(User $doctor): Appointment
    {
        return Appointment::create([
            'appointment_no' => Appointment::AppointmentNo(),
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $doctor->id,
            'notes'          => '原始备注',
            '_who_added'     => $doctor->id,
        ]);
    }

    private function claimFor(User $doctor, string $status = DoctorClaim::STATUS_PENDING): DoctorClaim
    {
        $rate = ClaimRate::create([
            'cash_rate'      => 10,
            'insurance_rate' => 20,
            'status'         => 'Active',
            'doctor_id'      => $doctor->id,
            '_who_added'     => $doctor->id,
        ]);

        $claim = DoctorClaim::create([
            'claim_amount'   => 500,
            'appointment_id' => $this->appointmentFor($doctor)->id,
            'claim_rate_id'  => $rate->id,
            '_who_added'     => $doctor->id,
        ]);

        // status 不在 fillable 里，测试要造已审批数据只能直接写
        $claim->status = $status;
        $claim->save();

        return $claim->fresh();
    }

    /** @test */
    public function 医生读不到别人的预约详情(): void
    {
        $others = $this->appointmentFor($this->doctorB);

        $response = $this->actingAs($this->doctorA)
            ->getJson('/doctor-appointments/' . $others->id . '/edit');

        $response->assertStatus(200);
        $this->assertEmpty($response->json(), '不该返回别人预约的任何字段');
    }

    /** @test */
    public function 医生改不了别人的预约(): void
    {
        $others = $this->appointmentFor($this->doctorB);

        $this->actingAs($this->doctorA)
            ->putJson('/doctor-appointments/' . $others->id, [
                'patient_id' => $this->patient->id,
                'notes'      => '被别人改了',
            ]);

        $this->assertSame('原始备注', $others->fresh()->notes);
    }

    /** @test */
    public function 医生删不掉别人的预约(): void
    {
        $others = $this->appointmentFor($this->doctorB);

        $this->actingAs($this->doctorA)
            ->deleteJson('/doctor-appointments/' . $others->id);

        $this->assertNull($others->fresh()->deleted_at, '别人的预约不应被软删');
    }

    /** @test */
    public function 医生改得了自己的预约(): void
    {
        $mine = $this->appointmentFor($this->doctorA);

        $this->actingAs($this->doctorA)
            ->putJson('/doctor-appointments/' . $mine->id, [
                'patient_id' => $this->patient->id,
                'notes'      => '我自己改的',
            ]);

        $this->assertSame('我自己改的', $mine->fresh()->notes);
    }

    /** @test */
    public function 没有预约权限的角色进不了医生预约模块(): void
    {
        $this->actingAs($this->receptionist)
            ->getJson('/doctor-appointments')
            ->assertStatus(403);
    }

    /** @test */
    public function 医生改不了别人的提成金额(): void
    {
        $others = $this->claimFor($this->doctorB);

        $this->actingAs($this->doctorA)
            ->putJson('/claims/' . $others->id, ['amount' => 99999]);

        $fresh = $others->fresh();
        $this->assertEquals(500, $fresh->claim_amount, '别人的提成金额不应被改');
        $this->assertSame($this->doctorB->id, $fresh->_who_added, '_who_added 不应被改写成调用者');
    }

    /** @test */
    public function 医生删不掉别人的提成(): void
    {
        $others = $this->claimFor($this->doctorB);

        $this->actingAs($this->doctorA)
            ->deleteJson('/claims/' . $others->id);

        $this->assertNull($others->fresh()->deleted_at);
    }

    /** @test */
    public function 已审批的提成改不动(): void
    {
        $approved = $this->claimFor($this->doctorA, DoctorClaim::STATUS_APPROVED);

        $this->actingAs($this->doctorA)
            ->putJson('/claims/' . $approved->id, ['amount' => 88888]);

        $this->assertEquals(500, $approved->fresh()->claim_amount);
    }

    /** @test */
    public function 医生改得了自己待审批的提成(): void
    {
        $mine = $this->claimFor($this->doctorA);

        $this->actingAs($this->doctorA)
            ->putJson('/claims/' . $mine->id, ['amount' => 777])
            ->assertJson(['status' => true]);

        $this->assertEquals(777, $mine->fresh()->claim_amount);
    }
}
