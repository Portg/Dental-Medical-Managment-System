<?php

namespace Tests\Feature;

use App\Branch;
use App\Invoice;
use App\InvoicePayment;
use App\MemberTransaction;
use App\Patient;
use App\Role;
use App\Services\InvoicePaymentService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * 储值支付必须四条路径一致：新增、混合、修改、撤销。
 *
 * 此前只有 processMixedPayment() 会扣患者余额并写会员流水，另外三条都不动余额：
 *   - API 单笔收款选 StoredValue → 账单记着「储值已付」，患者卡里分文未动
 *   - 把现金改成储值 / 把储值改成现金 → 同样不动余额
 *   - 撤销一笔储值收款 → 余额不退回，那笔钱凭空消失
 * 对账时两边永远合不上，而且是无声的：患者要到下次消费才发现余额不对。
 */
class StoredValuePaymentConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private Patient $patient;
    private Invoice $invoice;
    private InvoicePaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Administrator', 'slug' => 'admin']);

        $this->cashier = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $branch->id,
            'status'    => User::STATUS_ACTIVE,
        ]);

        Auth::login($this->cashier);

        $this->patient = Patient::create([
            'patient_no'     => '20260701',
            'surname'        => '赵',
            'othername'      => '六',
            'gender'         => 'Male',
            'member_balance' => 2000,
            '_who_added'     => $this->cashier->id,
        ]);

        $this->invoice = Invoice::create([
            'invoice_no'   => 'INV-SV-1',
            'invoice_date' => now()->format('Y-m-d'),
            'total_amount' => 1000,
            'paid_amount'  => 0,
            'patient_id'   => $this->patient->id,
            'branch_id'    => $branch->id,
            '_who_added'   => $this->cashier->id,
        ]);

        $this->service = app(InvoicePaymentService::class);
    }

    private function storedValuePayload(float $amount = 300): array
    {
        return [
            'amount'         => $amount,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => InvoicePaymentService::METHOD_STORED_VALUE,
            'invoice_id'     => $this->invoice->id,
        ];
    }

    /** @test */
    public function 单笔储值收款会扣余额并写流水(): void
    {
        $this->service->createPayment($this->storedValuePayload(300));

        $this->assertEquals(1700, $this->patient->fresh()->member_balance, '储值收款没有从卡里扣钱');
        $this->assertEquals(300, $this->invoice->fresh()->paid_amount);

        $this->assertDatabaseHas('member_transactions', [
            'patient_id'       => $this->patient->id,
            'transaction_type' => 'Consumption',
            'invoice_id'       => $this->invoice->id,
        ]);
    }

    /** @test */
    public function 余额不足时整笔收款回滚(): void
    {
        $this->patient->update(['member_balance' => 100]);

        try {
            $this->service->createPayment($this->storedValuePayload(300));
            $this->fail('余额不足却收下了这笔储值支付');
        } catch (\RuntimeException $e) {
            // 预期
        }

        $this->assertSame(0, InvoicePayment::count(), '余额不足时不该留下收款记录');
        $this->assertEquals(100, $this->patient->fresh()->member_balance);
        $this->assertEquals(0, $this->invoice->fresh()->paid_amount);
    }

    /** @test */
    public function 撤销储值收款会把余额退回去(): void
    {
        $payment = $this->service->createPayment($this->storedValuePayload(300));
        $this->assertEquals(1700, $this->patient->fresh()->member_balance);

        $this->service->deletePayment($payment->id);

        $this->assertEquals(2000, $this->patient->fresh()->member_balance, '撤销储值收款没有退回余额');
        $this->assertEquals(0, $this->invoice->fresh()->paid_amount);

        $this->assertDatabaseHas('member_transactions', [
            'patient_id'       => $this->patient->id,
            'transaction_type' => 'Refund',
            'invoice_id'       => $this->invoice->id,
        ]);
    }

    /** @test */
    public function 撤销普通收款不会动余额(): void
    {
        $payment = $this->service->createPayment([
            'amount'         => 300,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'invoice_id'     => $this->invoice->id,
        ]);

        $this->service->deletePayment($payment->id);

        $this->assertEquals(2000, $this->patient->fresh()->member_balance);
        $this->assertSame(0, MemberTransaction::count());
    }

    /**
     * 就地改储值收款要么改金额、要么改方式，两种都得同步调余额并再写一条流水，
     * 一条流水就对应不上一笔事实了。规则是「先撤销（余额退回）再重新登记」。
     */
    /** @test */
    public function 储值收款不能就地修改(): void
    {
        $payment = $this->service->createPayment($this->storedValuePayload(300));

        $this->expectException(\RuntimeException::class);

        try {
            $this->service->updatePayment($payment->id, [
                'amount'         => 500,
                'payment_date'   => now()->format('Y-m-d'),
                'payment_method' => InvoicePaymentService::METHOD_STORED_VALUE,
            ]);
        } finally {
            $this->assertEquals(300, $payment->fresh()->amount);
            $this->assertEquals(1700, $this->patient->fresh()->member_balance);
        }
    }

    /** @test */
    public function 普通收款不能改成储值(): void
    {
        $payment = $this->service->createPayment([
            'amount'         => 300,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'invoice_id'     => $this->invoice->id,
        ]);

        $this->expectException(\RuntimeException::class);

        try {
            $this->service->updatePayment($payment->id, [
                'amount'         => 300,
                'payment_date'   => now()->format('Y-m-d'),
                'payment_method' => InvoicePaymentService::METHOD_STORED_VALUE,
            ]);
        } finally {
            $this->assertSame('Cash', $payment->fresh()->payment_method);
            $this->assertEquals(2000, $this->patient->fresh()->member_balance, '改方式失败却把余额扣了');
        }
    }

    /** @test */
    public function 储值收款不能改成现金(): void
    {
        $payment = $this->service->createPayment($this->storedValuePayload(300));

        $this->expectException(\RuntimeException::class);

        try {
            $this->service->updatePayment($payment->id, [
                'amount'         => 300,
                'payment_date'   => now()->format('Y-m-d'),
                'payment_method' => 'Cash',
            ]);
        } finally {
            $this->assertSame(
                InvoicePaymentService::METHOD_STORED_VALUE,
                $payment->fresh()->payment_method,
                '改成现金会让那笔已扣的余额再也退不回来'
            );
            $this->assertEquals(1700, $this->patient->fresh()->member_balance);
        }
    }

    /**
     * 「读余额 → 判够不够 → 写回」必须先把卡锁住。
     *
     * 不锁的话同一个会员同时结两张账单会这样交错：两边都读到 2000、都判定够付
     * 300、都写回 1700 —— 卡里少扣了一笔。单进程造不出真并发（RefreshDatabase
     * 把用例包在事务里，另一条连接看不到数据），这里钉的是结构：读余额那条
     * SELECT 必须带 for update。少了它，锁就不存在。
     */
    /** @test */
    public function 扣储值前会锁住卡(): void
    {
        $lockedRead = false;

        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$lockedRead) {
            $sql = strtolower($query->sql);

            if (str_starts_with($sql, 'select') && str_contains($sql, 'patients')
                && str_contains($sql, 'for update')) {
                $lockedRead = true;
            }
        });

        $this->service->createPayment($this->storedValuePayload(300));

        $this->assertTrue($lockedRead, '扣余额前没锁患者行，同一会员并发结账会少扣');
    }

    /**
     * 已退费的账单不许再撤销收款。
     *
     * 撤销退的是这笔付款的全额，退费退的是账单层面的金额，两者没有分摊关系：
     * 储值付 300 → 退费 100 → 再撤销付款，患者卡里一共多出 400，凭空多了 100。
     */
    /** @test */
    public function 已退费的账单撤销不了收款(): void
    {
        $payment = $this->service->createPayment($this->storedValuePayload(300));
        $this->assertEquals(1700, $this->patient->fresh()->member_balance);

        \App\Refund::create([
            'refund_no'       => \App\Refund::generateRefundNo(),
            'invoice_id'      => $this->invoice->id,
            'patient_id'      => $this->patient->id,
            'refund_amount'   => 100,
            'refund_reason'   => '多收',
            'refund_date'     => now(),
            'refund_method'   => 'stored_value',
            'approval_status' => \App\Refund::APPROVAL_APPROVED,
            'branch_id'       => $this->invoice->branch_id,
            '_who_added'      => $this->cashier->id,
        ]);

        $this->expectException(\RuntimeException::class);

        try {
            $this->service->deletePayment($payment->id);
        } finally {
            $this->assertNotNull($payment->fresh(), '收款不该被撤销');
            $this->assertEquals(1700, $this->patient->fresh()->member_balance, '余额被重复退回了');
        }
    }

    /** @test */
    public function 待审批的退费不挡撤销收款(): void
    {
        $payment = $this->service->createPayment($this->storedValuePayload(300));

        \App\Refund::create([
            'refund_no'       => \App\Refund::generateRefundNo(),
            'invoice_id'      => $this->invoice->id,
            'patient_id'      => $this->patient->id,
            'refund_amount'   => 100,
            'refund_reason'   => '待审批',
            'refund_date'     => now(),
            'refund_method'   => 'stored_value',
            'approval_status' => \App\Refund::APPROVAL_PENDING,
            'branch_id'       => $this->invoice->branch_id,
            '_who_added'      => $this->cashier->id,
        ]);

        $this->assertTrue($this->service->deletePayment($payment->id));
        $this->assertEquals(2000, $this->patient->fresh()->member_balance);
    }

    /**
     * 共享储值卡：扣哪张卡，就得退回哪张卡。
     *
     * 扣款走 resolvePrimaryMember，从主卡持有人余额里扣；退费此前直接给
     * refund->patient_id 加余额。副卡患者消费时就成了「主卡扣款、副卡退款」——
     * 两个人的余额各错一笔，而且金额一样大，总账看着还是平的，很难发现。
     */
    /** @test */
    public function 共享卡的扣款与退款落在同一张卡上(): void
    {
        $primary = Patient::create([
            'patient_no'     => '20260702',
            'surname'        => '钱',
            'othername'      => '主卡',
            'gender'         => 'Male',
            'member_balance' => 5000,
            '_who_added'     => $this->cashier->id,
        ]);

        \App\MemberSharedHolder::create([
            'primary_patient_id' => $primary->id,
            'shared_patient_id'  => $this->patient->id,
            'is_active'          => true,
            '_who_added'         => $this->cashier->id,
        ]);

        // 副卡患者消费：钱从主卡扣
        $this->service->createPayment($this->storedValuePayload(300));

        $this->assertEquals(4700, $primary->fresh()->member_balance, '共享卡消费应当扣主卡');
        $this->assertEquals(2000, $this->patient->fresh()->member_balance, '副卡余额不该动');

        // 退费也必须退回主卡
        $refund = \App\Refund::create([
            'refund_no'       => \App\Refund::generateRefundNo(),
            'invoice_id'      => $this->invoice->id,
            'patient_id'      => $this->patient->id,
            'refund_amount'   => 300,
            'refund_reason'   => '取消治疗',
            'refund_date'     => now(),
            'refund_method'   => 'stored_value',
            'approval_status' => \App\Refund::APPROVAL_PENDING,
            'branch_id'       => $this->invoice->branch_id,
            '_who_added'      => $this->cashier->id,
        ]);

        $result = app(\App\Services\RefundService::class)->approveRefund($refund->id, $this->cashier->id);
        $this->assertTrue($result['status'], $result['message'] ?? '');

        $this->assertEquals(5000, $primary->fresh()->member_balance, '退费应当退回主卡');
        $this->assertEquals(2000, $this->patient->fresh()->member_balance, '副卡不该凭空多出一笔');
    }

    /**
     * 积分与累计消费的口径不能取决于走哪个入口，撤销也必须冲回去。
     *
     * 此前只有混合支付会加积分与累计消费：同样一笔 500 元现金，走 /payments/mixed
     * 有积分、走 /payments 没有；而且两条路径撤销时都不回退 —— 收了再撤就是白拿积分。
     */
    /** @test */
    public function 单笔收款也给积分与累计消费且撤销会冲回(): void
    {
        $level = \App\MemberLevel::create([
            'name'         => '金卡',
            'level_code'   => 'GOLD',
            'min_amount'   => 0,
            'points_rate'  => 1,
            'discount_rate' => 100,
            'is_active'    => true,
        ]);

        $this->patient->update(['member_level_id' => $level->id, 'member_points' => 0, 'total_consumption' => 0]);

        $payment = $this->service->createPayment([
            'amount'         => 500,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'invoice_id'     => $this->invoice->id,
        ]);

        $afterPay = $this->patient->fresh();
        $this->assertEquals(500, $afterPay->total_consumption, '单笔收款没有计入累计消费');
        $awarded = (int) $afterPay->member_points;
        $this->assertGreaterThan(0, $awarded, '单笔收款没有给积分');

        // 积分流水要挂在这笔收款上，撤销才冲得准
        $this->assertDatabaseHas('member_transactions', [
            'invoice_payment_id' => $payment->id,
            'transaction_type'   => 'Points',
            'points_change'      => $awarded,
        ]);

        $this->service->deletePayment($payment->id);

        $afterCancel = $this->patient->fresh();
        $this->assertEquals(0, $afterCancel->total_consumption, '撤销收款没有冲回累计消费');
        $this->assertEquals(0, $afterCancel->member_points, '撤销收款没有冲回积分——白拿积分');
    }

    /**
     * 混合支付本来就是对的，这里钉住它与单笔走的是同一段逻辑（同样的流水、同样的扣减），
     * 免得日后又各改各的。
     */
    /** @test */
    public function 混合支付里的储值与单笔口径一致(): void
    {
        $result = $this->service->processMixedPayment($this->invoice->id, [
            ['payment_method' => InvoicePaymentService::METHOD_STORED_VALUE, 'amount' => 400],
            ['payment_method' => 'Cash', 'amount' => 100],
        ]);

        $this->assertTrue($result['status'], $result['message'] ?? '');
        $this->assertEquals(1600, $this->patient->fresh()->member_balance);
        $this->assertEquals(500, $this->invoice->fresh()->paid_amount);

        $this->assertSame(
            1,
            MemberTransaction::where('transaction_type', 'Consumption')->count(),
            '储值消费流水应当只有一条'
        );
    }
}
