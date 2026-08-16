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

    /**
     * 登记收款的校验此前只有 required，比同一控制器里的 update() 宽得多。
     *
     * 三个洞各自的后果：
     *   金额非数字 → 落库成 0，账单显示「收过款」但一分没进；负数 → 直接冲减合计
     *   支付方式随便填 → 收据打印页认不出，原样把这串字印给患者
     *   账单号指向不存在的账单 → 服务层拿 null 继续跑，500
     */
    /** @test */
    public function 收款金额必须是正数(): void
    {
        foreach ([-100, 0, 'abc', ''] as $amount) {
            $this->actingAs($this->cashier)
                ->postJson('/payments', $this->paymentPayload(['amount' => $amount]))
                ->assertStatus(422);
        }

        $this->assertSame(0, InvoicePayment::count());
        $this->assertEquals(0, $this->invoice->fresh()->paid_amount);
    }

    /** @test */
    public function 收款方式必须是系统认得的那几种(): void
    {
        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload(['payment_method' => '微信扫码']))
            ->assertStatus(422);

        $this->assertSame(0, InvoicePayment::count());

        // 白名单里的照常收
        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload(['payment_method' => 'WeChat']))
            ->assertStatus(200)->assertJson(['status' => true]);
    }

    /** @test */
    public function 收款不能挂在不存在的账单上(): void
    {
        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload(['invoice_id' => $this->invoice->id + 999]))
            ->assertStatus(422);

        $this->assertSame(0, InvoicePayment::count());
    }

    /** @test */
    public function 收款日期必须是日期(): void
    {
        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload(['payment_date' => '昨天']))
            ->assertStatus(422);

        $this->assertSame(0, InvoicePayment::count());
    }

    /**
     * 重算必须在事务里、并且先锁住账单行。
     *
     * 不锁的话两笔并发收款会这样交错：A 插入 → A 求和 → B 插入 → B 求和 →
     * B 写回 → A 用过期的汇总覆盖回去，账单的已收金额停在 A 那一笔。
     * SQLite 没有行锁，这里钉的是「求和与写回被包在同一个事务里」这个结构 ——
     * 少了它，MySQL 上的 lockForUpdate 出了事务范围立刻失效。
     */
    /** @test */
    public function 重算已收金额包在事务里且先锁账单行(): void
    {
        // RefreshDatabase 自己把用例包在一层事务里，所以「level > 0」是恒真的，
        // 要跟这个基线比才说明重算另外开了自己的事务（嵌套时是 savepoint）。
        $baseline = \Illuminate\Support\Facades\DB::transactionLevel();

        $updateLevel = null;
        $lockedRead  = false;

        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$updateLevel, &$lockedRead) {
            $sql = strtolower($query->sql);

            if (str_starts_with($sql, 'update') && str_contains($sql, 'invoices')) {
                $updateLevel = \Illuminate\Support\Facades\DB::transactionLevel();
            }

            if (str_starts_with($sql, 'select') && str_contains($sql, 'invoices')
                && str_contains($sql, 'for update')) {
                $lockedRead = true;
            }
        });

        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload())
            ->assertJson(['status' => true]);

        $this->assertNotNull($updateLevel, '没有观察到账单的写回');
        $this->assertGreaterThan(
            $baseline,
            $updateLevel,
            '重算没有开自己的事务，求和与写回之间敞着，并发收款会互相覆盖'
        );
        $this->assertTrue($lockedRead, '求和之前没有锁住账单行，并发重算仍会用过期汇总覆盖');
    }

    /**
     * 混合支付的「不超过欠款」必须在锁内读。
     *
     * 原先是先读 outstanding_amount 判断超没超、再开事务插收款，中间敞着：
     * 欠款 1000 的账单上两笔并发的 600 元各自都读到 1000、都判定没超，最后收进 1200。
     * 超收只能靠退费纠正，而退费要走审批。单进程造不出真并发，这里钉结构 ——
     * 那次带 for update 的账单读取必须发生在收款插入之前。
     */
    /** @test */
    public function 混合支付在锁内判断是否超收(): void
    {
        $order = [];

        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$order) {
            $sql = strtolower($query->sql);

            if (str_starts_with($sql, 'select') && str_contains($sql, 'invoices')
                && str_contains($sql, 'for update')) {
                $order[] = 'lock';
            }

            if (str_starts_with($sql, 'insert') && str_contains($sql, 'invoice_payments')) {
                $order[] = 'insert';
            }
        });

        $this->actingAs($this->cashier)
            ->postJson('/payments/mixed', [
                'invoice_id' => $this->invoice->id,
                'payments'   => [['payment_method' => 'Cash', 'amount' => 600]],
            ])
            ->assertJson(['status' => true]);

        $this->assertContains('lock', $order, '没有锁住账单行就判断了是否超收');
        $this->assertContains('insert', $order, '没有观察到收款插入');
        $this->assertLessThan(
            array_search('insert', $order, true),
            array_search('lock', $order, true),
            '账单是在插入收款之后才锁的，超收校验读的仍是没锁的旧值'
        );
    }

    /** @test */
    public function 超过欠款的混合支付被拒(): void
    {
        $this->actingAs($this->cashier)
            ->postJson('/payments/mixed', [
                'invoice_id' => $this->invoice->id,
                'payments'   => [['payment_method' => 'Cash', 'amount' => 1500]],
            ])
            ->assertJson(['status' => false]);

        $this->assertSame(0, InvoicePayment::count());
        $this->assertEquals(0, $this->invoice->fresh()->paid_amount);
    }

    /**
     * 支票 / 保险 / 往来账户的附加信息不能被静默丢弃。
     *
     * 收款弹窗一直在提交这几项（invoices/payment/create.blade.php），但 store()
     * 既不校验也不往服务层传，createPayment() 里那几个 `?? null` 于是永远取到 null：
     * 收款保存成功，单据上却查不到是哪张支票、哪家保司，对账时无从追溯。
     */
    /** @test */
    public function 支票收款会保存支票号与银行(): void
    {
        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload([
                'payment_method' => 'Cheque',
                'cheque_no'      => 'CHQ-20260816',
                'bank_name'      => '中国银行',
                'account_name'   => '张三',
            ]))
            ->assertStatus(200)->assertJson(['status' => true]);

        $this->assertDatabaseHas('invoice_payments', [
            'invoice_id'   => $this->invoice->id,
            'cheque_no'    => 'CHQ-20260816',
            'bank_name'    => '中国银行',
            'account_name' => '张三',
        ]);
    }

    /** @test */
    public function 支票收款缺支票号或银行时被拒(): void
    {
        foreach ([['bank_name' => '中国银行'], ['cheque_no' => 'CHQ-1']] as $partial) {
            $this->actingAs($this->cashier)
                ->postJson('/payments', $this->paymentPayload(
                    ['payment_method' => 'Cheque'] + $partial
                ))
                ->assertStatus(422);
        }

        $this->assertSame(0, InvoicePayment::count());
    }

    /**
     * 单笔收款也不能超收。
     *
     * 此前只有混合支付那条路上有欠款判断，/payments 能给欠款 1000 的账单
     * 登记 1500 —— 超收只能靠退费纠正，而退费要走审批。
     */
    /** @test */
    public function 单笔收款不能超过欠款(): void
    {
        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload(['amount' => 1500]))
            ->assertStatus(422);

        $this->assertSame(0, InvoicePayment::count());
        $this->assertEquals(0, $this->invoice->fresh()->paid_amount);

        // 分两笔收，第二笔加起来超了也要拦
        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload(['amount' => 800]))
            ->assertJson(['status' => true]);

        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload(['amount' => 300]))
            ->assertStatus(422);

        $this->assertEquals(800, $this->invoice->fresh()->paid_amount);
    }

    /**
     * 折扣还没审批时不能收款。
     *
     * canAcceptPayment() 一直只有混合支付在看，单笔收款绕过了整条折扣审批链。
     */
    /** @test */
    public function 折扣待审批的账单收不了单笔款(): void
    {
        $this->invoice->discount_amount = Invoice::DISCOUNT_APPROVAL_THRESHOLD + 100;
        $this->invoice->discount_approval_status = Invoice::DISCOUNT_PENDING;
        $this->invoice->save();

        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload(['amount' => 100]))
            ->assertStatus(422);

        $this->assertSame(0, InvoicePayment::count());
    }

    /**
     * 超收判断按明细算，不读 invoices.outstanding_amount。
     *
     * 那一列由 paid_amount 派生，而 paid_amount 正是可能漂掉的值。拿漂掉的欠款
     * 去卡收款，会把本该收得下的钱拦掉 —— 账单记着 900、明细一条都没有时，
     * 存储的欠款是 100，而实际一分没收，1000 都该收得进来。
     */
    /** @test */
    public function 超收判断不受已漂移的已收金额影响(): void
    {
        $this->invoice->paid_amount = 900;
        $this->invoice->save();
        $this->assertEquals(100, $this->invoice->fresh()->outstanding_amount);

        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload(['amount' => 1000]))
            ->assertStatus(200)->assertJson(['status' => true]);

        $this->assertEquals(1000, $this->invoice->fresh()->paid_amount);
    }

    /**
     * 注意这个入口校验失败时返的是 200 + status:false，不是 422 —— 前端
     * （patient_billing.js）按 data.status 分支，改成 422 会掉进 error 回调。
     */
    /** @test */
    public function 混合支付也认付款方式白名单与条件必填(): void
    {
        $this->actingAs($this->cashier)
            ->postJson('/payments/mixed', [
                'invoice_id' => $this->invoice->id,
                'payments'   => [['payment_method' => 'Bitcoin', 'amount' => 100]],
            ])
            ->assertJson(['status' => false]);

        $this->actingAs($this->cashier)
            ->postJson('/payments/mixed', [
                'invoice_id' => $this->invoice->id,
                'payments'   => [['payment_method' => 'Cheque', 'amount' => 100]],
            ])
            ->assertJson(['status' => false]);

        $this->assertSame(0, InvoicePayment::count());

        // 带齐了就放行
        $this->actingAs($this->cashier)
            ->postJson('/payments/mixed', [
                'invoice_id' => $this->invoice->id,
                'payments'   => [[
                    'payment_method' => 'Cheque',
                    'amount'         => 100,
                    'cheque_no'      => 'CHQ-9',
                    'bank_name'      => '工商银行',
                ]],
            ])
            ->assertJson(['status' => true]);

        $this->assertDatabaseHas('invoice_payments', ['cheque_no' => 'CHQ-9', 'bank_name' => '工商银行']);
    }

    /**
     * 改已有收款同样不能超收。
     *
     * updatePayment() 原先直接改金额再重算，锁、欠款校验、折扣审批一样都没有 ——
     * 而 API 的 update 恰好允许传新金额：账单 1000、已收 500，把那笔改成 1500，
     * paid_amount 就超过账单总额了。
     */
    /** @test */
    public function 改收款金额不能改到超过账单总额(): void
    {
        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload(['amount' => 500]))
            ->assertJson(['status' => true]);

        $payment = InvoicePayment::first();

        $this->actingAs($this->cashier)
            ->putJson('/payments/' . $payment->id, [
                'payment_method' => 'Cash',
                'amount'         => 1500,
            ])
            ->assertStatus(422);

        $this->assertEquals(500, $payment->fresh()->amount);
        $this->assertEquals(500, $this->invoice->fresh()->paid_amount);
    }

    /**
     * 判超收时要先把这笔自己的旧金额摘出去，否则「1000 改成 900」也会被自己挡住。
     */
    /** @test */
    public function 改小金额与改到刚好收满都放行(): void
    {
        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload(['amount' => 1000]))
            ->assertJson(['status' => true]);

        $payment = InvoicePayment::first();

        $this->actingAs($this->cashier)
            ->putJson('/payments/' . $payment->id, ['payment_method' => 'Cash', 'amount' => 900])
            ->assertJson(['status' => true]);

        $this->assertEquals(900, $this->invoice->fresh()->paid_amount);

        $this->actingAs($this->cashier)
            ->putJson('/payments/' . $payment->id, ['payment_method' => 'Cash', 'amount' => 1000])
            ->assertJson(['status' => true]);

        $this->assertEquals(1000, $this->invoice->fresh()->paid_amount);
    }

    /** @test */
    public function 折扣待审批时也改不了收款(): void
    {
        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload(['amount' => 100]))
            ->assertJson(['status' => true]);

        $payment = InvoicePayment::first();

        $this->invoice->discount_amount = Invoice::DISCOUNT_APPROVAL_THRESHOLD + 100;
        $this->invoice->discount_approval_status = Invoice::DISCOUNT_PENDING;
        $this->invoice->save();

        $this->actingAs($this->cashier)
            ->putJson('/payments/' . $payment->id, ['payment_method' => 'Cash', 'amount' => 200])
            ->assertStatus(422);

        $this->assertEquals(100, $payment->fresh()->amount);
    }

    /** @test */
    public function 保险收款会保存保险公司(): void
    {
        $company = \App\InsuranceCompany::create([
            'name'       => '平安健康',
            '_who_added' => $this->cashier->id,
        ]);

        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload([
                'payment_method'       => 'Insurance',
                'insurance_company_id' => $company->id,
            ]))
            ->assertStatus(200)->assertJson(['status' => true]);

        $this->assertDatabaseHas('invoice_payments', [
            'invoice_id'           => $this->invoice->id,
            'insurance_company_id' => $company->id,
        ]);

        // 不带保险公司的保险收款要被拦下，否则又是一笔查不到对手方的收款
        $this->actingAs($this->cashier)
            ->postJson('/payments', $this->paymentPayload(['payment_method' => 'Insurance']))
            ->assertStatus(422);
    }
}
