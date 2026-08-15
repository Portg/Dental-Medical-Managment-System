<?php

namespace Tests\Feature;

use App\Branch;
use App\Invoice;
use App\InvoicePayment;
use App\Patient;
use App\Permission;
use App\Refund;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 收款是钱进来的那一步，此前零测试覆盖。
 *
 * 这里钉的是三件事：谁能收款（三档权限并非同一个）、收了钱有没有真的落库、
 * 撤销收款走的是不是软删（硬删会让对账查无对证）。
 */
class InvoicePaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private User $viewer;
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);

        $cashierRole = Role::create(['name' => 'Cashier', 'slug' => 'cashier']);
        $viewerRole  = Role::create(['name' => 'Viewer', 'slug' => 'viewer']);

        $perms = [];
        foreach (['view-invoices', 'create-invoices', 'edit-invoices'] as $slug) {
            $perms[$slug] = Permission::create([
                'name'   => $slug,
                'slug'   => $slug,
                'module' => '账单管理',
            ]);
        }

        // 收银：看得到、能收款、能改
        foreach ($perms as $perm) {
            RolePermission::create(['role_id' => $cashierRole->id, 'permission_id' => $perm->id]);
        }
        // 只读：只有 view-invoices
        RolePermission::create([
            'role_id'       => $viewerRole->id,
            'permission_id' => $perms['view-invoices']->id,
        ]);

        $this->cashier = User::factory()->create([
            'role_id'   => $cashierRole->id,
            'branch_id' => $branch->id,
            'status'    => 'active',
        ]);

        $this->viewer = User::factory()->create([
            'role_id'   => $viewerRole->id,
            'branch_id' => $branch->id,
            'status'    => 'active',
        ]);

        $patient = Patient::create([
            'patient_no' => '20260901',
            'surname'    => '王',
            'othername'  => '五',
            'gender'     => 'Male',
            'phone_no'   => '13800138002',
            '_who_added' => $this->cashier->id,
        ]);

        $this->invoice = Invoice::create([
            'invoice_no'   => 'INV20260901',
            'invoice_date' => now()->format('Y-m-d'),
            'total_amount' => 1000,
            'paid_amount'  => 0,
            'patient_id'   => $patient->id,
            'branch_id'    => $branch->id,
            '_who_added'   => $this->cashier->id,
        ]);

        Cache::flush();
    }

    private function paymentPayload(array $overrides = []): array
    {
        return array_merge([
            'amount'         => 300,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'invoice_id'     => $this->invoice->id,
        ], $overrides);
    }

    /** @test */
    public function 收款会落库并记在正确的账单上(): void
    {
        $response = $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload());

        $response->assertStatus(200)->assertJson(['status' => true]);

        $this->assertDatabaseHas('invoice_payments', [
            'invoice_id'     => $this->invoice->id,
            'amount'         => 300,
            'payment_method' => 'Cash',
            '_who_added'     => $this->cashier->id,
        ]);
    }

    /** @test */
    public function 收款必填项缺失时拒绝(): void
    {
        foreach (['amount', 'payment_date', 'payment_method', 'invoice_id'] as $field) {
            $payload = $this->paymentPayload();
            unset($payload[$field]);

            $this->actingAs($this->cashier)
                ->postJson('/payments', $payload)
                ->assertStatus(422);
        }

        $this->assertSame(0, InvoicePayment::count());
    }

    /**
     * 只读角色不能收款。
     *
     * 控制器把 view / create / edit 拆成了三档，别在重构时合并成一个
     * can:view-invoices —— 那等于让所有能看账单的人都能收钱。
     */
    /** @test */
    public function 只读角色收不了款(): void
    {
        $this->actingAs($this->viewer)
            ->postJson('/payments', $this->paymentPayload())
            ->assertStatus(403);

        $this->assertSame(0, InvoicePayment::count());
    }

    /** @test */
    public function 只读角色改不了收款方式(): void
    {
        $payment = InvoicePayment::create([
            'amount'         => 300,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'invoice_id'     => $this->invoice->id,
            'branch_id'      => $this->invoice->branch_id,
            '_who_added'     => $this->cashier->id,
        ]);

        $this->actingAs($this->viewer)
            ->putJson('/payments/' . $payment->id, ['payment_method' => 'Cheque'])
            ->assertStatus(403);

        $this->assertSame('Cash', $payment->fresh()->payment_method);
    }

    /**
     * 改收款方式不该顺手把金额清零。
     *
     * 控制器专门做了「请求没带 amount / payment_date 就沿用原值」的兜底，
     * 因为改收款方式的弹窗只提交方式相关字段。
     */
    /** @test */
    public function 只改收款方式时金额与日期保持不变(): void
    {
        $payment = InvoicePayment::create([
            'amount'         => 300,
            'payment_date'   => '2026-08-01',
            'payment_method' => 'Cash',
            'invoice_id'     => $this->invoice->id,
            'branch_id'      => $this->invoice->branch_id,
            '_who_added'     => $this->cashier->id,
        ]);

        $this->actingAs($this->cashier)
            ->putJson('/payments/' . $payment->id, ['payment_method' => 'Mobile Money'])
            ->assertStatus(200)
            ->assertJson(['status' => true]);

        $fresh = $payment->fresh();
        $this->assertSame('Mobile Money', $fresh->payment_method);
        $this->assertEquals(300, $fresh->amount);
        // payment_date 没有 cast，取出来是裸字符串
        $this->assertStringStartsWith('2026-08-01', (string) $fresh->payment_date);
    }

    /** @test */
    public function 支票必须带支票号与银行(): void
    {
        $payment = InvoicePayment::create([
            'amount'         => 300,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'invoice_id'     => $this->invoice->id,
            'branch_id'      => $this->invoice->branch_id,
            '_who_added'     => $this->cashier->id,
        ]);

        $this->actingAs($this->cashier)
            ->putJson('/payments/' . $payment->id, ['payment_method' => 'Cheque'])
            ->assertStatus(422);

        $this->assertSame('Cash', $payment->fresh()->payment_method);
    }

    /**
     * 撤销收款必须是软删：对账要能查到这笔钱曾经存在、又被谁撤掉。
     */
    /** @test */
    public function 撤销收款走软删而非硬删(): void
    {
        $payment = InvoicePayment::create([
            'amount'         => 300,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'invoice_id'     => $this->invoice->id,
            'branch_id'      => $this->invoice->branch_id,
            '_who_added'     => $this->cashier->id,
        ]);

        $this->actingAs($this->cashier)
            ->deleteJson('/payments/' . $payment->id)
            ->assertStatus(200)
            ->assertJson(['status' => true]);

        $this->assertNull(InvoicePayment::find($payment->id));
        $this->assertNotNull(InvoicePayment::withTrashed()->find($payment->id)->deleted_at);
    }

    /** @test */
    public function 改不存在的收款返回404而不是500(): void
    {
        $this->actingAs($this->cashier)
            ->putJson('/payments/999999', ['payment_method' => 'Cash'])
            ->assertStatus(404)
            ->assertJson(['status' => false]);
    }

    // ── 账单已收金额的同步 ──────────────────────────────────────────
    //
    // invoice.paid_amount 是存储列，outstanding_amount 与 payment_status 由
    // Invoice::boot() 的 saving 钩子据它派生。此前只有 processMixedPayment() 会
    // 更新它，走 /payments 的单笔收款、改金额、撤销收款统统不碰 —— 列表页靠子查询
    // 算 computed_paid 才显示对，而存储的 payment_status 一直停在「未付」，
    // 凡是按这个字段筛的地方（欠费报表、催收）就都不准。

    /** @test */
    public function 收款后账单的已收金额与状态跟着更新(): void
    {
        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload(['amount' => 300]))
            ->assertJson(['status' => true]);

        $invoice = $this->invoice->fresh();
        $this->assertEquals(300, $invoice->paid_amount);
        $this->assertEquals(700, $invoice->outstanding_amount);
        $this->assertSame(Invoice::PAYMENT_PARTIAL, $invoice->payment_status);
    }

    /** @test */
    public function 收满全款后账单状态变已付清(): void
    {
        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload(['amount' => 1000]))
            ->assertJson(['status' => true]);

        $invoice = $this->invoice->fresh();
        $this->assertEquals(1000, $invoice->paid_amount);
        $this->assertEquals(0, $invoice->outstanding_amount);
        $this->assertSame(Invoice::PAYMENT_PAID, $invoice->payment_status);
    }

    /** @test */
    public function 改收款金额后账单跟着重算(): void
    {
        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload(['amount' => 300]))
            ->assertJson(['status' => true]);

        $payment = InvoicePayment::first();

        $this->actingAs($this->cashier)
            ->putJson('/payments/' . $payment->id, [
                'payment_method' => 'Cash',
                'amount'         => 800,
            ])->assertJson(['status' => true]);

        $invoice = $this->invoice->fresh();
        $this->assertEquals(800, $invoice->paid_amount);
        $this->assertEquals(200, $invoice->outstanding_amount);
    }

    /** @test */
    public function 撤销收款后账单退回未付(): void
    {
        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload(['amount' => 1000]))
            ->assertJson(['status' => true]);

        $this->assertSame(Invoice::PAYMENT_PAID, $this->invoice->fresh()->payment_status);

        $payment = InvoicePayment::first();

        $this->actingAs($this->cashier)
            ->deleteJson('/payments/' . $payment->id)
            ->assertJson(['status' => true]);

        $invoice = $this->invoice->fresh();
        $this->assertEquals(0, $invoice->paid_amount);
        $this->assertEquals(1000, $invoice->outstanding_amount);
        $this->assertSame(Invoice::PAYMENT_UNPAID, $invoice->payment_status);
    }

    /** @test */
    public function 多笔收款按明细累计(): void
    {
        foreach ([200, 300, 100] as $amount) {
            $this->actingAs($this->cashier)
                ->postJson('/payments', $this->paymentPayload(['amount' => $amount]))
                ->assertJson(['status' => true]);
        }

        $this->assertEquals(600, $this->invoice->fresh()->paid_amount);
    }

    /**
     * 重算而非增减，所以历史漂移会在下一次收款时自愈。
     */
    /** @test */
    public function 已经漂掉的已收金额会在下次收款时被纠正(): void
    {
        // 模拟历史漂移：账单上记着 900，实际一条收款明细都没有
        $this->invoice->paid_amount = 900;
        $this->invoice->save();

        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload(['amount' => 200]))
            ->assertJson(['status' => true]);

        $this->assertEquals(200, $this->invoice->fresh()->paid_amount, '应按明细重算，而不是在 900 上再加 200');
    }

    /**
     * 重算必须把已通过的退费扣掉。
     *
     * RefundService::executeRefund() 在退费通过时把退款从 paid_amount 里减掉，
     * 退费本身不会产生 invoice_payments 行。如果只按付款明细求和，这笔扣减会被
     * 抹平 —— 一张已退费的账单会在下次动收款时跳回「全额已付」。
     */
    /** @test */
    public function 重算会扣除已通过的退费(): void
    {
        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload(['amount' => 1000]))
            ->assertJson(['status' => true]);

        Refund::create([
            'refund_no'       => Refund::generateRefundNo(),
            'invoice_id'      => $this->invoice->id,
            'patient_id'      => $this->invoice->patient_id,
            'refund_amount'   => 400,
            'refund_reason'   => '多收',
            'refund_date'     => now(),
            'refund_method'   => 'cash',
            'approval_status' => Refund::APPROVAL_APPROVED,
            'branch_id'       => $this->invoice->branch_id,
            '_who_added'      => $this->cashier->id,
        ]);

        // 再动一次收款，触发重算
        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload(['amount' => 100]))
            ->assertJson(['status' => true]);

        // 付款 1000 + 100，减去已通过退费 400
        $this->assertEquals(700, $this->invoice->fresh()->paid_amount);
    }

    /** @test */
    public function 待审批的退费不参与扣减(): void
    {
        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload(['amount' => 1000]))
            ->assertJson(['status' => true]);

        Refund::create([
            'refund_no'       => Refund::generateRefundNo(),
            'invoice_id'      => $this->invoice->id,
            'patient_id'      => $this->invoice->patient_id,
            'refund_amount'   => 400,
            'refund_reason'   => '待审批',
            'refund_date'     => now(),
            'refund_method'   => 'cash',
            'approval_status' => Refund::APPROVAL_PENDING,
            'branch_id'       => $this->invoice->branch_id,
            '_who_added'      => $this->cashier->id,
        ]);

        // 换个付款方式，让 update 真的影响到行（相同值时 update 返回 0 行，
        // 控制器会当成失败——那是 (bool) update() 的既有语义，与本用例无关）
        $payment = InvoicePayment::first();
        $this->actingAs($this->cashier)
            ->putJson('/payments/' . $payment->id, ['payment_method' => 'Mobile Money'])
            ->assertJson(['status' => true]);

        $this->assertEquals(1000, $this->invoice->fresh()->paid_amount, '钱还没退出去，不该先扣');
    }
}
