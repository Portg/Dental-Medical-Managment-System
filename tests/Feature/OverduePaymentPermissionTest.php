<?php

namespace Tests\Feature;

use App\Branch;
use App\Invoice;
use App\InvoicePayment;
use App\Patient;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 补收欠款 / 减免尾款：两件事，两种权限。
 *
 * 这个接口原来整个挂在 edit-invoices 上，而 edit-invoices 只有 super-admin 和
 * admin 有 —— 前台，诊所里唯一负责收钱的人，补收不了欠款。那不是权限设计上的
 * 取舍，是漏配。
 *
 * 现在按请求实际带了什么分别判：
 *   amount              → 收钱，要 collect-payments
 *   additional_discount → 减免尾款（把欠款一笔勾掉），是授权动作，要 edit-invoices
 *
 * 这组用例钉四件事：
 *   1. 前台（有收款权限、无改单权限）能补收欠款 —— 这是修的那个漏配
 *   2. 前台不能减免尾款 —— 收钱和勾账不是一回事
 *   3. 有改单权限的人能减免
 *   4. 金额和减免都为 0 的请求被挡 —— 否则两道判定都不触发，
 *      只有 view-invoices 的角色也能走到写入分支
 */
class OverduePaymentPermissionTest extends TestCase
{
    use RefreshDatabase;

    private User $frontDesk;   // collect-payments，无 edit-invoices
    private User $manager;     // edit-invoices + collect-payments
    private User $viewer;      // 只有 view-invoices
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);

        $perms = [];
        foreach (['view-invoices', 'edit-invoices', 'collect-payments'] as $slug) {
            $perms[$slug] = Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $slug, 'module' => '账单管理']
            )->id;
        }

        $roles = [
            'front-desk' => ['view-invoices', 'collect-payments'],
            'manager'    => ['view-invoices', 'collect-payments', 'edit-invoices'],
            'viewer'     => ['view-invoices'],
        ];
        $roleIds = [];
        foreach ($roles as $slug => $grants) {
            $role = Role::create(['name' => $slug, 'slug' => $slug]);
            foreach ($grants as $g) {
                RolePermission::create(['role_id' => $role->id, 'permission_id' => $perms[$g]]);
            }
            $roleIds[$slug] = $role->id;
        }

        foreach (['frontDesk' => 'front-desk', 'manager' => 'manager', 'viewer' => 'viewer'] as $prop => $slug) {
            $this->{$prop} = User::factory()->create([
                'role_id' => $roleIds[$slug], 'branch_id' => $branch->id, 'status' => User::STATUS_ACTIVE,
            ]);
        }

        $patient = Patient::create([
            'patient_no' => '20260920', 'surname' => '冯', 'othername' => '十二',
            'gender' => 'Male', 'phone_no' => '13800138012', '_who_added' => $this->manager->id,
        ]);

        // 欠 400 的账单：总额 1000，已付 600
        $this->invoice = Invoice::create([
            'invoice_no' => 'INV-OVERDUE-1', 'invoice_date' => now()->format('Y-m-d'),
            'total_amount' => 1000, 'paid_amount' => 600,
            'patient_id' => $patient->id, 'branch_id' => $branch->id,
            '_who_added' => $this->manager->id,
        ]);

        // 那 600 必须有真实的收款记录：paid_amount 由 syncInvoicePaidAmount()
        // 按 invoice_payments 重算，只在 invoices 上写个数字的话，补收之后
        // 会被重算成只有新那一笔 —— 夹具不自洽就测不出真实行为。
        InvoicePayment::create([
            'amount' => 600, 'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'Cash', 'invoice_id' => $this->invoice->id,
            'branch_id' => $branch->id, '_who_added' => $this->manager->id,
        ]);

        Cache::flush();
    }

    private function url(): string
    {
        return '/invoices/' . $this->invoice->id . '/add-overdue-payment';
    }

    /**
     * 这条就是修的那个漏配：前台是收钱的人，必须能补收欠款。
     */
    /** @test */
    public function 前台能补收欠款(): void
    {
        $this->actingAs($this->frontDesk)
            ->postJson($this->url(), [
                'amount'         => 400,
                'payment_method' => 'Cash',
            ])
            ->assertStatus(200)
            ->assertJson(['status' => 1]);

        $this->assertEquals(1000, $this->invoice->fresh()->paid_amount);
        $this->assertSame(2, InvoicePayment::where('invoice_id', $this->invoice->id)->count(), '期初 600 + 补收 400');
    }

    /**
     * 收钱和勾账不是一回事：减免是把欠款一笔抹掉，属于授权动作。
     */
    /** @test */
    public function 前台不能减免尾款(): void
    {
        $this->actingAs($this->frontDesk)
            ->postJson($this->url(), ['additional_discount' => 400])
            ->assertStatus(403);

        $fresh = $this->invoice->fresh();
        $this->assertEquals(1000, $fresh->total_amount, '减免被拒后总额不该变');
        $this->assertEquals(0, $fresh->discount_amount);
    }

    /** @test */
    public function 有改单权限的人能减免尾款(): void
    {
        $this->actingAs($this->manager)
            ->postJson($this->url(), ['additional_discount' => 400])
            ->assertStatus(200)
            ->assertJson(['status' => 1]);

        $fresh = $this->invoice->fresh();
        $this->assertEquals(600, $fresh->total_amount, '减免 400 后总额应当降到 600');
        $this->assertEquals(400, $fresh->discount_amount);
    }

    /**
     * 前台带着减免一起提交时，整个请求被拒 —— 不能只收钱、把减免那半悄悄丢掉。
     */
    /** @test */
    public function 前台带减免的混合请求整笔被拒(): void
    {
        $this->actingAs($this->frontDesk)
            ->postJson($this->url(), [
                'amount'              => 200,
                'additional_discount' => 200,
                'payment_method'      => 'Cash',
            ])
            ->assertStatus(403);

        $this->assertSame(1, InvoicePayment::where('invoice_id', $this->invoice->id)->count(), '只该留下期初那笔');
        $this->assertEquals(600, $this->invoice->fresh()->paid_amount, '不该只把钱收了');
    }

    /** @test */
    public function 只读角色补收不了也减免不了(): void
    {
        $this->actingAs($this->viewer)
            ->postJson($this->url(), ['amount' => 400, 'payment_method' => 'Cash'])
            ->assertStatus(403);

        $this->actingAs($this->viewer)
            ->postJson($this->url(), ['additional_discount' => 400])
            ->assertStatus(403);

        $this->assertSame(1, InvoicePayment::where('invoice_id', $this->invoice->id)->count(), '只该留下期初那笔');
        $this->assertEquals(1000, $this->invoice->fresh()->total_amount);
    }

    /**
     * 金额和减免都是 0 的请求：两道权限判定都不触发，只有 view-invoices 的角色
     * 就能一路走到 Service 的写入分支。必须在判定之前挡掉。
     */
    /** @test */
    public function 金额与减免都为零的请求被挡(): void
    {
        $this->actingAs($this->viewer)
            ->postJson($this->url(), ['amount' => 0, 'additional_discount' => 0])
            ->assertStatus(422);

        $this->assertSame(1, InvoicePayment::where('invoice_id', $this->invoice->id)->count(), '只该留下期初那笔');
    }

    /** @test */
    public function 补收金额不能超过欠款(): void
    {
        $this->actingAs($this->frontDesk)
            ->postJson($this->url(), [
                'amount'         => 500,   // 只欠 400
                'payment_method' => 'Cash',
            ])
            ->assertStatus(422);

        $this->assertSame(1, InvoicePayment::where('invoice_id', $this->invoice->id)->count(), '只该留下期初那笔');
    }
}
