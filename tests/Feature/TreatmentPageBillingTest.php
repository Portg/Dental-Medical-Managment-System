<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\Invoice;
use App\MedicalService;
use App\Patient;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 诊疗页划价：账单必须挂到这次就诊上，且不能挂到别人的就诊上。
 *
 * 诊疗页原来走的是另一条开单路（AddInvoice 弹窗 → POST /invoices），与患者页的
 * /billing/create 各自实现折扣、牙位、医生归属，改一处漏一处已经出过几次问题。
 * 现在两条合成一条：诊疗页复用患者页的划价面板，额外带一个 appointment_id。
 *
 * 这组用例钉三件事：
 *   1. 带了 appointment_id，账单就挂在这次就诊上 —— 诊疗页「本次已划价」表按
 *      invoices.appointment_id 过滤，不挂上去医生开完单在同一个页面什么都看不到
 *   2. 不带 appointment_id 照样能开（患者页划价没有就诊上下文）
 *   3. appointment_id 必须属于所传的患者 —— exists 只保证预约存在，
 *      两个 id 各自独立，改一下请求就能把账单挂到别人的就诊记录上
 */
class TreatmentPageBillingTest extends TestCase
{
    use RefreshDatabase;

    private User $frontDesk;
    private Patient $patient;
    private Patient $otherPatient;
    private Appointment $appointment;
    private Appointment $othersAppointment;
    private MedicalService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Front Desk', 'slug' => 'front-desk']);

        foreach (['view-invoices', 'create-invoices', 'collect-payments'] as $slug) {
            $perm = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => '账单管理']);
            RolePermission::create(['role_id' => $role->id, 'permission_id' => $perm->id]);
        }

        $this->frontDesk = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $branch->id, 'status' => User::STATUS_ACTIVE,
        ]);

        $this->patient = Patient::create([
            'patient_no' => '20260910', 'surname' => '吴', 'othername' => '十',
            'gender' => 'Male', 'phone_no' => '13800138010', '_who_added' => $this->frontDesk->id,
        ]);
        $this->otherPatient = Patient::create([
            'patient_no' => '20260911', 'surname' => '郑', 'othername' => '十一',
            'gender' => 'Female', 'phone_no' => '13800138011', '_who_added' => $this->frontDesk->id,
        ]);

        $this->appointment       = $this->makeAppointment($this->patient, $branch);
        $this->othersAppointment = $this->makeAppointment($this->otherPatient, $branch);

        $this->service = MedicalService::create([
            'name' => '根管治疗', 'price' => 1200, '_who_added' => $this->frontDesk->id,
        ]);

        Cache::flush();
    }

    private function makeAppointment(Patient $patient, Branch $branch): Appointment
    {
        return Appointment::create([
            'patient_id'       => $patient->id,
            'doctor_id'        => $this->frontDesk->id,
            'branch_id'        => $branch->id,
            'appointment_date' => now()->format('Y-m-d'),
            'start_time'       => '09:00',
            'end_time'         => '09:30',
            '_who_added'       => $this->frontDesk->id,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'patient_id' => $this->patient->id,
            'items' => [[
                'medical_service_id' => $this->service->id,
                'qty'   => 1,
                'price' => 1200,
            ]],
        ], $overrides);
    }

    /** @test */
    public function 诊疗页划价的账单挂在这次就诊上(): void
    {
        $this->actingAs($this->frontDesk)
            ->postJson('/billing/create', $this->payload([
                'appointment_id' => $this->appointment->id,
                'billing_mode'   => 'front_desk',
            ]))
            ->assertStatus(200)
            ->assertJson(['status' => true]);

        $invoice = Invoice::where('patient_id', $this->patient->id)->latest('id')->first();

        $this->assertNotNull($invoice);
        $this->assertSame(
            $this->appointment->id,
            (int) $invoice->appointment_id,
            '不挂上 appointment_id，诊疗页「本次已划价」表就是空的'
        );
    }

    /**
     * 「本次已划价」表就是按 invoices.appointment_id 过滤的，这里直接查那条链路，
     * 保证挂载真的能让明细表看到刚开的单。
     */
    /** @test */
    public function 挂上就诊后本次已划价表能查到(): void
    {
        $this->actingAs($this->frontDesk)
            ->postJson('/billing/create', $this->payload([
                'appointment_id' => $this->appointment->id,
                'billing_mode'   => 'front_desk',
            ]))
            ->assertStatus(200);

        $items = app(\App\Services\InvoiceItemService::class)
            ->getItemsByAppointment($this->appointment->id);

        $this->assertCount(1, $items);
        $this->assertSame('根管治疗', $items->first()->service_name);
        // 金额列读的是 line_amount：遗留列 invoice_items.amount 两条开单路径都没人写，
        // 一直是 0，明细表的金额列因此长期显示 0
        $this->assertEquals(1200, $items->first()->line_amount);
    }

    /**
     * 赠送项目（实付 0）不该被当成缺值而回退到原价。
     *
     * 这就是 line_amount 用 COALESCE 而不是 NULLIF(x, 0) 的原因 ——
     * 后者会把合法的 0 当成没填，把免费项目按原价显示出来。
     */
    /** @test */
    public function 实付为零的赠送项目金额显示零而不是原价(): void
    {
        $this->actingAs($this->frontDesk)
            ->postJson('/billing/create', [
                'patient_id'     => $this->patient->id,
                'appointment_id' => $this->appointment->id,
                'billing_mode'   => 'front_desk',
                'items' => [[
                    'medical_service_id' => $this->service->id,
                    'qty'   => 1,
                    'price' => 1200,
                    'discounted_price' => 0,
                    'actual_paid'      => 0,
                ]],
            ])
            ->assertStatus(200);

        $item = app(\App\Services\InvoiceItemService::class)
            ->getItemsByAppointment($this->appointment->id)
            ->first();

        $this->assertEquals(0, $item->line_amount, '赠送项目应当显示 0，不该回退成 1200');
    }

    /** @test */
    public function 患者页划价不带就诊也能开单(): void
    {
        $this->actingAs($this->frontDesk)
            ->postJson('/billing/create', $this->payload(['billing_mode' => 'front_desk']))
            ->assertStatus(200)
            ->assertJson(['status' => true]);

        $invoice = Invoice::where('patient_id', $this->patient->id)->latest('id')->first();

        $this->assertNotNull($invoice);
        $this->assertNull($invoice->appointment_id, '患者页划价没有就诊上下文，应当留空');
    }

    /**
     * exists:appointments,id 只保证预约存在，不保证是这个患者的。
     * patient_id 与 appointment_id 是两个独立字段，改一下请求就能把账单
     * 挂到别人的就诊记录上 —— 那既污染对方病历，也污染按就诊统计的报表。
     */
    /** @test */
    public function 不能把账单挂到别人的就诊上(): void
    {
        $this->actingAs($this->frontDesk)
            ->postJson('/billing/create', $this->payload([
                'appointment_id' => $this->othersAppointment->id,
                'billing_mode'   => 'front_desk',
            ]))
            ->assertStatus(422);

        $this->assertSame(0, Invoice::count(), '交叉核对失败时不该留下任何账单');
    }

    /** @test */
    public function 不存在的就诊id被拒(): void
    {
        $this->actingAs($this->frontDesk)
            ->postJson('/billing/create', $this->payload(['appointment_id' => 999999]))
            ->assertStatus(200)   // 校验失败走 {status:false}，与该接口既有约定一致
            ->assertJson(['status' => false]);

        $this->assertSame(0, Invoice::count());
    }

    /** @test */
    public function 诊疗页划价并当场收款账单同样挂在这次就诊上(): void
    {
        $this->actingAs($this->frontDesk)
            ->postJson('/billing/create', $this->payload([
                'appointment_id' => $this->appointment->id,
                'billing_mode'   => 'direct',
                'payments'       => [['payment_method' => 'Cash', 'amount' => 1200]],
            ]))
            ->assertStatus(200)
            ->assertJson(['status' => true]);

        $invoice = Invoice::where('patient_id', $this->patient->id)->latest('id')->first();

        $this->assertSame($this->appointment->id, (int) $invoice->appointment_id);
        $this->assertEquals(1200, $invoice->paid_amount);
    }
}
