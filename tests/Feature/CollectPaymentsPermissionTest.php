<?php

namespace Tests\Feature;

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
 * 收款（collect-payments）与开单（create-invoices）必须是两条权限。
 *
 * 拆分之前一条 create-invoices 同时管着开单和收款，于是「医生划价、前台收费」
 * 这种行业标准分工在系统里根本表达不出来 —— 医生要么什么都不能碰，要么开单收款
 * 全给。e看牙把「创建收费」和「收费」分成两条，牙医管家的说明也是「医生处置划价、
 * 前台完成收费」。
 *
 * 这组用例钉三件事：
 *   1. 只有开单权限的人能划价、能转前台待收，但收不了钱
 *   2. 划价接口不能成为绕过收款权限的后门（billing_mode=direct 带 payments）
 *   3. 有收款权限的人照常收款（拆分没有削弱前台）
 */
class CollectPaymentsPermissionTest extends TestCase
{
    use RefreshDatabase;

    private User $biller;   // 只能开单（模拟拿到 create-invoices 的医生）
    private User $cashier;  // 能开单也能收款（前台）
    private Patient $patient;
    private MedicalService $service;
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);

        $perms = [];
        foreach (['view-invoices', 'create-invoices', 'collect-payments'] as $slug) {
            $perms[$slug] = Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $slug, 'module' => '账单管理']
            )->id;
        }

        $billerRole  = Role::create(['name' => 'Biller', 'slug' => 'biller']);
        $cashierRole = Role::create(['name' => 'Cashier', 'slug' => 'cashier']);

        // 只开单，不收款
        foreach (['view-invoices', 'create-invoices'] as $slug) {
            RolePermission::create(['role_id' => $billerRole->id, 'permission_id' => $perms[$slug]]);
        }
        // 开单 + 收款
        foreach (['view-invoices', 'create-invoices', 'collect-payments'] as $slug) {
            RolePermission::create(['role_id' => $cashierRole->id, 'permission_id' => $perms[$slug]]);
        }

        $this->biller = User::factory()->create([
            'role_id' => $billerRole->id, 'branch_id' => $branch->id, 'status' => User::STATUS_ACTIVE,
        ]);
        $this->cashier = User::factory()->create([
            'role_id' => $cashierRole->id, 'branch_id' => $branch->id, 'status' => User::STATUS_ACTIVE,
        ]);

        $this->patient = Patient::create([
            'patient_no' => '20260901', 'surname' => '周', 'othername' => '八',
            'gender' => 'Male', 'phone_no' => '13800138008', '_who_added' => $this->cashier->id,
        ]);

        $this->service = MedicalService::create([
            'name' => '洁牙', 'price' => 200, '_who_added' => $this->cashier->id,
        ]);

        $this->invoice = Invoice::create([
            'invoice_no' => 'INV-PERM-1', 'invoice_date' => now()->format('Y-m-d'),
            'total_amount' => 1000, 'paid_amount' => 0,
            'patient_id' => $this->patient->id, 'branch_id' => $branch->id,
            '_who_added' => $this->cashier->id,
        ]);

        Cache::flush();
    }

    private function paymentPayload(): array
    {
        return [
            'amount'         => 300,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'invoice_id'     => $this->invoice->id,
        ];
    }

    private function billingPayload(array $overrides = []): array
    {
        return array_merge([
            'patient_id' => $this->patient->id,
            'items' => [[
                'medical_service_id' => $this->service->id,
                'qty' => 1,
                'price' => 200,
            ]],
        ], $overrides);
    }

    /** @test */
    public function 只有开单权限的人收不了款(): void
    {
        $this->actingAs($this->biller)
            ->postJson('/payments', $this->paymentPayload())
            ->assertStatus(403);

        $this->assertSame(0, \App\InvoicePayment::count());
    }

    /** @test */
    public function 有收款权限的人照常收款(): void
    {
        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload())
            ->assertStatus(200)
            ->assertJson(['status' => true]);

        $this->assertEquals(300, $this->invoice->fresh()->paid_amount);
    }

    /**
     * 划价（不带 payments）只要开单权限 —— 这正是「医生划价」要的那一半。
     */
    /** @test */
    public function 只有开单权限的人能划价并转前台待收(): void
    {
        $this->actingAs($this->biller)
            ->postJson('/billing/create', $this->billingPayload(['billing_mode' => 'front_desk']))
            ->assertStatus(200)
            ->assertJson(['status' => true]);

        // 账单开出来了，但一分钱都没收
        $created = Invoice::where('invoice_no', '<>', 'INV-PERM-1')->latest('id')->first();
        $this->assertNotNull($created, '应当开出一张新账单');
        $this->assertEquals(0, $created->paid_amount, '转前台待收不该当场收钱');
    }

    /**
     * 划价接口不能成为绕过收款权限的后门。
     *
     * billing_mode=direct 且带 payments 时，这个接口会当场登记收款 —— 不额外判一次
     * collect-payments 的话，只有开单权限的人能从这里直接把钱收掉。
     */
    /** @test */
    public function 划价接口不能绕过收款权限(): void
    {
        $this->actingAs($this->biller)
            ->postJson('/billing/create', $this->billingPayload([
                'billing_mode' => 'direct',
                'payments' => [['payment_method' => 'Cash', 'amount' => 200]],
            ]))
            ->assertStatus(403);

        $this->assertSame(0, \App\InvoicePayment::count(), '不该留下任何收款记录');
    }

    /** @test */
    public function 有收款权限的人可以划价并当场收款(): void
    {
        $this->actingAs($this->cashier)
            ->postJson('/billing/create', $this->billingPayload([
                'billing_mode' => 'direct',
                'payments' => [['payment_method' => 'Cash', 'amount' => 200]],
            ]))
            ->assertStatus(200)
            ->assertJson(['status' => true]);

        $this->assertSame(1, \App\InvoicePayment::count());
    }
}
