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

    // ── 提交提成（POST /claims）──────────────────────────────────────
    //
    // 上面几条钉的都是「按 id 读改删别人的记录」，但提成是先有 store 才有那些
    // 记录 —— 而 store 此前只校验两个字段非空，谁的预约、提没提过、金额是不是
    // 数字，一概不问。

    private function activeRateFor(User $doctor): ClaimRate
    {
        return ClaimRate::create([
            'cash_rate'      => 10,
            'insurance_rate' => 20,
            'status'         => ClaimRate::STATUS_ACTIVE,
            'doctor_id'      => $doctor->id,
            '_who_added'     => $doctor->id,
        ]);
    }

    /** @test */
    public function 医生提得了自己接诊的提成(): void
    {
        $this->activeRateFor($this->doctorA);
        $mine = $this->appointmentFor($this->doctorA);

        $this->actingAs($this->doctorA)
            ->postJson('/claims', ['appointment_id' => $mine->id, 'amount' => 500])
            ->assertStatus(200)
            ->assertJson(['status' => true]);

        $this->assertDatabaseHas('doctor_claims', [
            'appointment_id' => $mine->id,
            'claim_amount'   => 500,
            '_who_added'     => $this->doctorA->id,
        ]);
    }

    /**
     * 列表和日历都按 appointments.doctor_id 过滤，界面上看不到别人的预约；
     * 但 POST 的 appointment_id 来自请求体，换个数字就能给同事的接诊
     * 开一张自己的提成单，而且套的是自己的提成比例。
     */
    /** @test */
    public function 医生提不了别人接诊的提成(): void
    {
        $this->activeRateFor($this->doctorA);
        $others = $this->appointmentFor($this->doctorB);

        $this->actingAs($this->doctorA)
            ->postJson('/claims', ['appointment_id' => $others->id, 'amount' => 500])
            ->assertStatus(422);

        $this->assertDatabaseMissing('doctor_claims', ['appointment_id' => $others->id]);
    }

    /**
     * 一个预约只能提一次成。
     *
     * DoctorAppointmentService::appointmentHasClaim() 只是用来决定要不要显示
     * 「申请提成」按钮，接口侧从没跟上 —— 重复 POST 就能对同一次就诊反复提成。
     */
    /** @test */
    public function 同一个预约不能重复提成(): void
    {
        $this->activeRateFor($this->doctorA);
        $mine = $this->appointmentFor($this->doctorA);

        $this->actingAs($this->doctorA)
            ->postJson('/claims', ['appointment_id' => $mine->id, 'amount' => 500])
            ->assertJson(['status' => true]);

        $this->actingAs($this->doctorA)
            ->postJson('/claims', ['appointment_id' => $mine->id, 'amount' => 500])
            ->assertStatus(422);

        $this->assertSame(1, DoctorClaim::where('appointment_id', $mine->id)->count());
    }

    /**
     * 「查不存在 → 建」之间的并发窗口必须被锁住。
     *
     * 上一条测的是顺序重复提交（第二次能查到已有提成，所以拦得住）；真正的窗口是
     * 连点两次或两个标签页同时提交，两个请求都查不到提成，各建一条。单进程造不出
     * 真并发，这里钉的是结构：归属查询要在事务里、且带 for update，
     * 提成插入也要在同一个事务里。少了这两样，锁就不存在。
     *
     * 不用 appointment_id 的唯一索引：提成是软删的，唯一索引会让「删掉重提」永久失败。
     */
    /** @test */
    public function 提成创建在事务内锁住预约行(): void
    {
        $this->activeRateFor($this->doctorA);
        $mine = $this->appointmentFor($this->doctorA);

        // RefreshDatabase 自己就把用例包在一层事务里，「level > 0」恒真，要跟基线比
        $baseline = \Illuminate\Support\Facades\DB::transactionLevel();

        $lockedRead = false;
        $insertLevel = null;

        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$lockedRead, &$insertLevel) {
            $sql = strtolower($query->sql);

            if (str_starts_with($sql, 'select') && str_contains($sql, 'appointments')
                && str_contains($sql, 'for update')) {
                $lockedRead = \Illuminate\Support\Facades\DB::transactionLevel() > 0;
            }

            if (str_starts_with($sql, 'insert') && str_contains($sql, 'doctor_claims')) {
                $insertLevel = \Illuminate\Support\Facades\DB::transactionLevel();
            }
        });

        $this->actingAs($this->doctorA)
            ->postJson('/claims', ['appointment_id' => $mine->id, 'amount' => 500])
            ->assertJson(['status' => true]);

        $this->assertTrue($lockedRead, '归属查询没有在事务里加 for update，同一预约的并发提成不会排队');
        $this->assertNotNull($insertLevel, '没有观察到提成插入');
        $this->assertGreaterThan($baseline, $insertLevel, '提成插入落在自己的事务外，锁跨不到写入这一步');
    }

    /** @test */
    public function 提成金额必须是非负数字(): void
    {
        $this->activeRateFor($this->doctorA);
        $mine = $this->appointmentFor($this->doctorA);

        foreach ([-1, 'abc'] as $amount) {
            $this->actingAs($this->doctorA)
                ->postJson('/claims', ['appointment_id' => $mine->id, 'amount' => $amount])
                ->assertStatus(422);
        }

        $this->actingAs($this->doctorA)
            ->putJson('/claims/' . $this->claimFor($this->doctorA)->id, ['amount' => -500])
            ->assertStatus(422);

        $this->assertSame(0, DoctorClaim::where('appointment_id', $mine->id)->count());
    }

    /**
     * 模块门禁不能只靠 view-appointments。
     *
     * DefaultRolePermissionsSeeder 把这条权限同时发给了医生、护士、前台和管理员，
     * 上面那条「没有预约权限的角色进不了」用的是一个人为构造的、不持有该权限的
     * 前台，与正式权限配置对不上，所以测不出这个洞。这里按种子里真实的前台来 ——
     * 有 view-appointments，但不是医生。
     */
    /** @test */
    public function 持有预约权限的前台仍然进不了医生模块(): void
    {
        $frontDesk = User::factory()->create([
            // 与 setUp 里的前台同角色，因此同样持有 view-appointments
            'role_id'   => $this->doctorA->role_id,
            'branch_id' => $this->doctorA->branch_id,
            'is_doctor' => false,
            'password'  => bcrypt('password'),
        ]);

        $this->assertTrue(
            $frontDesk->can('view-appointments'),
            '前提：这个账号持有 view-appointments，否则测的就不是门禁本身'
        );

        $this->actingAs($frontDesk)->getJson('/claims')->assertStatus(403);
        $this->actingAs($frontDesk)->getJson('/doctor-appointments')->assertStatus(403);

        $this->activeRateFor($frontDesk);
        $this->actingAs($frontDesk)
            ->postJson('/claims', [
                'appointment_id' => $this->appointmentFor($this->doctorA)->id,
                'amount'         => 500,
            ])
            ->assertStatus(403);

        $this->assertSame(0, DoctorClaim::count());
    }
}
