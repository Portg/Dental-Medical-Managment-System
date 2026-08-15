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
 * 提成审批（/doctor-claims），此前零测试覆盖。
 *
 * 与医生自助的 /claims 是两套东西：那边按 _who_added 限定本人、只能改待审批的
 * （见 DoctorSelfServiceOwnershipTest）；这边是财务角色审批他人提成，凭
 * manage-doctor-claims 进入，审批后要落下审批人。两者别互相套用权限。
 */
class DoctorClaimApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;
    private User $doctor;
    private DoctorClaim $claim;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);

        $financeRole = Role::create(['name' => 'Finance', 'slug' => 'finance']);
        $doctorRole  = Role::create(['name' => 'Doctor', 'slug' => 'doctor']);

        $perm = Permission::create([
            'name'   => '管理医生提成',
            'slug'   => 'manage-doctor-claims',
            'module' => '财务管理',
        ]);
        RolePermission::create(['role_id' => $financeRole->id, 'permission_id' => $perm->id]);

        $this->finance = User::factory()->create([
            'role_id'   => $financeRole->id,
            'branch_id' => $branch->id,
            'status'    => 'active',
        ]);

        $this->doctor = User::factory()->create([
            'role_id'   => $doctorRole->id,
            'branch_id' => $branch->id,
            'is_doctor' => 'yes',
            'status'    => 'active',
        ]);

        $patient = Patient::create([
            'patient_no' => '20261001',
            'surname'    => '赵',
            'othername'  => '六',
            'gender'     => 'Female',
            'phone_no'   => '13800138003',
            '_who_added' => $this->finance->id,
        ]);

        $appointment = Appointment::create([
            'appointment_no' => Appointment::AppointmentNo(),
            'patient_id'     => $patient->id,
            'doctor_id'      => $this->doctor->id,
            'branch_id'      => $branch->id,
            '_who_added'     => $this->doctor->id,
        ]);

        $rate = ClaimRate::create([
            'cash_rate'      => 10,
            'insurance_rate' => 20,
            'status'         => 'Active',
            'doctor_id'      => $this->doctor->id,
            '_who_added'     => $this->finance->id,
        ]);

        $this->claim = DoctorClaim::create([
            'claim_amount'   => 1000,
            'appointment_id' => $appointment->id,
            'claim_rate_id'  => $rate->id,
            '_who_added'     => $this->doctor->id,
        ]);

        Cache::flush();
    }

    /** @test */
    public function 新建的提成默认待审批(): void
    {
        $this->assertSame(DoctorClaim::STATUS_PENDING, $this->claim->fresh()->status);
    }

    /** @test */
    public function 审批会落下金额状态与审批人(): void
    {
        $response = $this->actingAs($this->finance)
            ->postJson('/doctor-claims', [
                'id'               => $this->claim->id,
                'insurance_amount' => 600,
                'cash_amount'      => 400,
            ]);

        $response->assertStatus(200)->assertJson(['status' => true]);

        $fresh = $this->claim->fresh();
        $this->assertSame(DoctorClaim::STATUS_APPROVED, $fresh->status);
        $this->assertEquals(600, $fresh->insurance_amount);
        $this->assertEquals(400, $fresh->cash_amount);
        $this->assertSame($this->finance->id, $fresh->approved_by, '审批人必须记下来，否则出账查不到是谁批的');
    }

    /** @test */
    public function 审批金额必填(): void
    {
        foreach (['insurance_amount', 'cash_amount'] as $field) {
            $payload = [
                'id'               => $this->claim->id,
                'insurance_amount' => 600,
                'cash_amount'      => 400,
            ];
            unset($payload[$field]);

            $this->actingAs($this->finance)
                ->postJson('/doctor-claims', $payload)
                ->assertStatus(422);
        }

        $this->assertSame(DoctorClaim::STATUS_PENDING, $this->claim->fresh()->status);
    }

    /**
     * 医生持有的是自助权限，不该能审批自己的提成。
     */
    /** @test */
    public function 没有提成管理权限的角色审批不了(): void
    {
        $this->actingAs($this->doctor)
            ->postJson('/doctor-claims', [
                'id'               => $this->claim->id,
                'insurance_amount' => 9999,
                'cash_amount'      => 9999,
            ])
            ->assertStatus(403);

        $this->assertSame(DoctorClaim::STATUS_PENDING, $this->claim->fresh()->status);
    }

    /** @test */
    public function 没有提成管理权限的角色删不了提成(): void
    {
        $this->actingAs($this->doctor)
            ->deleteJson('/doctor-claims/' . $this->claim->id)
            ->assertStatus(403);

        $this->assertNotNull(DoctorClaim::find($this->claim->id));
    }

    /** @test */
    public function 删除提成走软删(): void
    {
        $this->actingAs($this->finance)
            ->deleteJson('/doctor-claims/' . $this->claim->id)
            ->assertStatus(200)
            ->assertJson(['status' => true]);

        $this->assertNull(DoctorClaim::find($this->claim->id));
        $this->assertNotNull(DoctorClaim::withTrashed()->find($this->claim->id)->deleted_at);
    }

    /** @test */
    public function 修改已审批提成会重新记录审批人(): void
    {
        $this->actingAs($this->finance)
            ->postJson('/doctor-claims', [
                'id'               => $this->claim->id,
                'insurance_amount' => 600,
                'cash_amount'      => 400,
            ])->assertJson(['status' => true]);

        $other = User::factory()->create([
            'role_id'   => $this->finance->role_id,
            'branch_id' => $this->finance->branch_id,
            'status'    => 'active',
        ]);

        $this->actingAs($other)
            ->putJson('/doctor-claims/' . $this->claim->id, [
                'insurance_amount' => 500,
                'cash_amount'      => 500,
            ])->assertJson(['status' => true]);

        $fresh = $this->claim->fresh();
        $this->assertEquals(500, $fresh->insurance_amount);
        $this->assertSame($other->id, $fresh->approved_by, '改过金额之后审批人要更新为最后经手人');
    }
}
