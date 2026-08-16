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

    /**
     * 待审批的退费同样要挡住撤销收款。
     *
     * 先撤销收款、再把那张待审批的退费单批掉，钱一样会退出去，只是把重复入账
     * 推迟到审批那一刻 —— 上一版只拦已通过的退费，正好漏掉这条路。
     */
    /** @test */
    public function 待审批的退费也挡撤销收款(): void
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

        $this->expectException(\RuntimeException::class);

        try {
            $this->service->deletePayment($payment->id);
        } finally {
            $this->assertNotNull($payment->fresh(), '收款不该被撤销');
            $this->assertEquals(1700, $this->patient->fresh()->member_balance);
        }
    }

    /**
     * 审批退费时要按当下的实收再核一次可退金额。
     *
     * 这是最后一道。前面的 assertNoOpenRefund 已经挡住了「挂着退费还去改金额/撤销」
     * 的正常路径，但实收仍可能因为别的原因与建单时对不上：升级前留下的退费单、
     * 直接改库的历史数据、以及日后新增的其他调整入口。所以这里绕开服务层直接改库，
     * 模拟「前置守卫没拦住」的状态，确认批的那一刻还会再核一次。
     */
    /** @test */
    public function 审批退费时按当下实收复核可退金额(): void
    {
        $payment = $this->service->createPayment([
            'amount'         => 800,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'invoice_id'     => $this->invoice->id,
        ]);

        $refund = \App\Refund::create([
            'refund_no'       => \App\Refund::generateRefundNo(),
            'invoice_id'      => $this->invoice->id,
            'patient_id'      => $this->patient->id,
            'refund_amount'   => 800,
            'refund_reason'   => '取消治疗',
            'refund_date'     => now(),
            'refund_method'   => 'cash',
            'approval_status' => \App\Refund::APPROVAL_PENDING,
            'branch_id'       => $this->invoice->branch_id,
            '_who_added'      => $this->cashier->id,
        ]);

        // 绕开服务层直接改库：模拟前置守卫之外的途径让实收缩水到 200
        InvoicePayment::where('id', $payment->id)->update(['amount' => 200]);
        $this->service->syncInvoicePaidAmount($this->invoice->id);
        $this->assertEquals(200, $this->invoice->fresh()->paid_amount);

        $result = app(\App\Services\RefundService::class)->approveRefund($refund->id, $this->cashier->id);

        $this->assertFalse($result['status'], '实收只剩 200，不该批得下 800 的退费');
        $this->assertSame(
            \App\Refund::APPROVAL_PENDING,
            $refund->fresh()->approval_status,
            '复核不通过时退费单不该被改成已批准'
        );
    }

    /**
     * 改收款金额之后，累计消费与积分要跟着重算。
     *
     * 收 500 后改成 100，不重算的话累计消费还停在 500；之后撤销只减 100，
     * 患者账上白留 400。
     */
    /** @test */
    public function 改收款金额会同步重算累计消费(): void
    {
        $payment = $this->service->createPayment([
            'amount'         => 500,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'invoice_id'     => $this->invoice->id,
        ]);

        $this->assertEquals(500, $this->patient->fresh()->total_consumption);

        $this->service->updatePayment($payment->id, [
            'amount'         => 100,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
        ]);

        $this->assertEquals(100, $this->patient->fresh()->total_consumption, '改金额后累计消费没跟着走');

        $this->service->deletePayment($payment->id);

        $this->assertEquals(0, $this->patient->fresh()->total_consumption, '撤销后累计消费应当归零');
    }

    /**
     * 历史收款（本次改动之前建的，从没加过累计消费）撤销时不能倒扣。
     *
     * 迁移只加列不回填，所以老记录的 member_benefits_awarded 是 false；
     * 不看这个标记就会把患者原本就有的累计消费白白减掉，还可能把等级降下去。
     */
    /** @test */
    public function 历史收款撤销不会倒扣累计消费(): void
    {
        $this->patient->update(['total_consumption' => 5000]);

        // 直接建记录，绕开 createPayment —— 模拟升级前留下的那批数据
        $legacy = InvoicePayment::create([
            'amount'         => 300,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'invoice_id'     => $this->invoice->id,
            'branch_id'      => $this->invoice->branch_id,
            '_who_added'     => $this->cashier->id,
        ]);

        $this->assertFalse((bool) $legacy->fresh()->member_benefits_awarded);

        $this->service->deletePayment($legacy->id);

        $this->assertEquals(
            5000,
            $this->patient->fresh()->total_consumption,
            '历史收款从没加过累计消费，撤销时不该倒扣'
        );
    }

    /**
     * 储值撤销要退给当初真正扣款的那张卡，而不是按现在的共享卡关系重新解析。
     *
     * 付款之后共享卡可能被解绑或改绑，按当前关系退会把钱退给另一个人 ——
     * 一个人凭空多一笔，另一个人凭空少一笔。
     */
    /** @test */
    public function 储值撤销按当初扣款的那张卡退回(): void
    {
        $primary = Patient::create([
            'patient_no'     => '20260703',
            'surname'        => '孙',
            'othername'      => '主卡',
            'gender'         => 'Male',
            'member_balance' => 5000,
            '_who_added'     => $this->cashier->id,
        ]);

        $holder = \App\MemberSharedHolder::create([
            'primary_patient_id' => $primary->id,
            'shared_patient_id'  => $this->patient->id,
            'is_active'          => true,
            '_who_added'         => $this->cashier->id,
        ]);

        $payment = $this->service->createPayment($this->storedValuePayload(300));
        $this->assertEquals(4700, $primary->fresh()->member_balance);

        // 付款之后解绑共享关系：现在 resolvePrimaryMember 会解析成患者自己
        $holder->update(['is_active' => false]);

        $this->service->deletePayment($payment->id);

        $this->assertEquals(5000, $primary->fresh()->member_balance, '钱是从主卡扣的，就该退回主卡');
        $this->assertEquals(2000, $this->patient->fresh()->member_balance, '副卡不该凭空多出一笔');
    }

    /** @test */
    public function 发放与冲销会员权益前会锁患者行(): void
    {
        $lockCount = 0;

        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$lockCount) {
            $sql = strtolower($query->sql);

            if (str_starts_with($sql, 'select') && str_contains($sql, 'patients')
                && str_contains($sql, 'for update')) {
                $lockCount++;
            }
        });

        $payment = $this->service->createPayment([
            'amount'         => 300,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'invoice_id'     => $this->invoice->id,
        ]);

        $this->assertGreaterThan(0, $lockCount, '发放累计消费前没锁患者行，并发结账会互相覆盖');

        $lockCount = 0;
        $this->service->deletePayment($payment->id);

        $this->assertGreaterThan(0, $lockCount, '冲销累计消费前没锁患者行');
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
     * 挂着退费时，改金额和撤销一样要挡。
     *
     * 撤销那条已经堵上了，改金额是同一个洞的另一半：收 500 → 退费 500 已批 →
     * 再把这笔改成 100，退出去的钱就超过实收了。
     */
    /** @test */
    public function 挂着退费时改不了收款金额(): void
    {
        $payment = $this->service->createPayment([
            'amount'         => 500,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'invoice_id'     => $this->invoice->id,
        ]);

        \App\Refund::create([
            'refund_no'       => \App\Refund::generateRefundNo(),
            'invoice_id'      => $this->invoice->id,
            'patient_id'      => $this->patient->id,
            'refund_amount'   => 500,
            'refund_reason'   => '取消治疗',
            'refund_date'     => now(),
            'refund_method'   => 'cash',
            'approval_status' => \App\Refund::APPROVAL_APPROVED,
            'branch_id'       => $this->invoice->branch_id,
            '_who_added'      => $this->cashier->id,
        ]);

        $this->expectException(\RuntimeException::class);

        try {
            $this->service->updatePayment($payment->id, [
                'amount'         => 100,
                'payment_date'   => now()->format('Y-m-d'),
                'payment_method' => 'Cash',
            ]);
        } finally {
            $this->assertEquals(500, $payment->fresh()->amount, '金额不该被改小');
        }
    }

    /** @test */
    public function 挂着退费时仍可只改收款方式(): void
    {
        $payment = $this->service->createPayment([
            'amount'         => 500,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'invoice_id'     => $this->invoice->id,
        ]);

        \App\Refund::create([
            'refund_no'       => \App\Refund::generateRefundNo(),
            'invoice_id'      => $this->invoice->id,
            'patient_id'      => $this->patient->id,
            'refund_amount'   => 200,
            'refund_reason'   => '部分退',
            'refund_date'     => now(),
            'refund_method'   => 'cash',
            'approval_status' => \App\Refund::APPROVAL_APPROVED,
            'branch_id'       => $this->invoice->branch_id,
            '_who_added'      => $this->cashier->id,
        ]);

        // 金额没变，只是纠正收款方式 —— 不影响退费金额，应当放行
        $this->assertTrue($this->service->updatePayment($payment->id, [
            'amount'         => 500,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => 'WeChat',
        ]));

        $this->assertSame('WeChat', $payment->fresh()->payment_method);
    }

    /**
     * 历史收款只改方式，不能凭空补上一份累计消费与积分。
     *
     * 老记录的 member_benefits_awarded 是 false，冲销会跳过（对的）；若照样重新
     * 发放，就等于给一笔从没计过消费的老单补一份，累计消费平白多出来。
     */
    /** @test */
    public function 改历史收款不会凭空补发会员权益(): void
    {
        $this->patient->update(['total_consumption' => 5000, 'member_points' => 0]);

        $legacy = InvoicePayment::create([
            'amount'         => 300,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'invoice_id'     => $this->invoice->id,
            'branch_id'      => $this->invoice->branch_id,
            '_who_added'     => $this->cashier->id,
        ]);

        $this->service->updatePayment($legacy->id, [
            'amount'         => 300,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => 'WeChat',
        ]);

        $this->assertEquals(
            5000,
            $this->patient->fresh()->total_consumption,
            '历史收款从没计过消费，改方式不该给它补上一份'
        );
        $this->assertFalse((bool) $legacy->fresh()->member_benefits_awarded);
    }

    /**
     * 积分已经被兑换掉时，撤销收款要拒绝而不是静默抹平。
     *
     * 原先是 max(0, 现有 − 当初发放)：账上只减得到剩下那点，流水却记「全额已冲销」，
     * 兑出去的储值追不回来，积分流水也对不平。这种情况得由人来决定怎么处理。
     */
    /** @test */
    public function 积分已被兑换时拒绝撤销收款(): void
    {
        $level = \App\MemberLevel::create([
            'name'          => '金卡',
            'level_code'    => 'GOLD2',
            'min_amount'    => 0,
            'points_rate'   => 1,
            'discount_rate' => 100,
            'is_active'     => true,
        ]);

        $this->patient->update(['member_level_id' => $level->id, 'member_points' => 0]);

        $payment = $this->service->createPayment([
            'amount'         => 500,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'invoice_id'     => $this->invoice->id,
        ]);

        $awarded = (int) $this->patient->fresh()->member_points;
        $this->assertGreaterThan(0, $awarded);

        // 患者把积分兑换掉了，账上只剩一点
        $this->patient->update(['member_points' => 1]);

        $this->expectException(\RuntimeException::class);

        try {
            $this->service->deletePayment($payment->id);
        } finally {
            $this->assertNotNull($payment->fresh(), '积分已用掉，这笔收款不该被撤销');
            $this->assertEquals(1, $this->patient->fresh()->member_points, '不该把剩余积分也抹掉');
        }
    }

    /**
     * 退费审批也要退给当初真正扣款的那张卡。
     *
     * 上一轮只修了「撤销收款」那条路径，退费审批仍按当前共享卡关系解析 ——
     * 付款到审批之间解绑共享卡，钱就退进了副卡。
     */
    /** @test */
    public function 退费审批按当初扣款的那张卡退回(): void
    {
        $primary = Patient::create([
            'patient_no'     => '20260704',
            'surname'        => '周',
            'othername'      => '主卡',
            'gender'         => 'Male',
            'member_balance' => 5000,
            '_who_added'     => $this->cashier->id,
        ]);

        $holder = \App\MemberSharedHolder::create([
            'primary_patient_id' => $primary->id,
            'shared_patient_id'  => $this->patient->id,
            'is_active'          => true,
            '_who_added'         => $this->cashier->id,
        ]);

        $this->service->createPayment($this->storedValuePayload(300));
        $this->assertEquals(4700, $primary->fresh()->member_balance);

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

        // 审批之前共享关系被解除
        $holder->update(['is_active' => false]);

        $result = app(\App\Services\RefundService::class)->approveRefund($refund->id, $this->cashier->id);
        $this->assertTrue($result['status'], $result['message'] ?? '');

        $this->assertEquals(5000, $primary->fresh()->member_balance, '钱是从主卡扣的，就该退回主卡');
        $this->assertEquals(2000, $this->patient->fresh()->member_balance, '副卡不该凭空多出一笔');
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
