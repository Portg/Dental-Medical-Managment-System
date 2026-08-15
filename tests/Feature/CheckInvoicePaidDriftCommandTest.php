<?php

namespace Tests\Feature;

use App\Branch;
use App\Invoice;
use App\InvoicePayment;
use App\Patient;
use App\Refund;
use App\Role;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * invoices:check-paid-drift 的体检对象是「存储的 paid_amount 与收款/退费明细
 * 对不上」。这条命令是上线前的一道门 —— 它报「没问题」而实际有问题，比没有
 * 这条命令更糟，所以这里把它自己的判断口径钉死。
 *
 * 尤其是退费：退费通过时 RefundService::executeRefund() 把退款从 paid_amount
 * 扣掉、且不产生收款行。命令若只按收款求和，会把每一张退过费的账单都误报成
 * 漂移 —— 在真实库上跑出一屏假阳性，等于让人没法判断。
 */
class CheckInvoicePaidDriftCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Patient $patient;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);

        $this->admin = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $this->branch->id,
            'status'    => 'active',
        ]);

        $this->patient = Patient::create([
            'patient_no' => '20261101',
            'surname'    => '孙',
            'othername'  => '七',
            'gender'     => 'Male',
            'phone_no'   => '13800138004',
            '_who_added' => $this->admin->id,
        ]);
    }

    private function makeInvoice(float $total, float $storedPaid): Invoice
    {
        return Invoice::create([
            'invoice_no'   => 'INV' . str_pad((string) (Invoice::count() + 1), 6, '0', STR_PAD_LEFT),
            'invoice_date' => now()->format('Y-m-d'),
            'total_amount' => $total,
            'paid_amount'  => $storedPaid,
            'patient_id'   => $this->patient->id,
            'branch_id'    => $this->branch->id,
            '_who_added'   => $this->admin->id,
        ]);
    }

    private function addPayment(Invoice $invoice, float $amount): InvoicePayment
    {
        return InvoicePayment::create([
            'amount'         => $amount,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'invoice_id'     => $invoice->id,
            'branch_id'      => $this->branch->id,
            '_who_added'     => $this->admin->id,
        ]);
    }

    private function addRefund(Invoice $invoice, float $amount, string $status): Refund
    {
        return Refund::create([
            'refund_no'       => Refund::generateRefundNo(),
            'invoice_id'      => $invoice->id,
            'patient_id'      => $invoice->patient_id,
            'refund_amount'   => $amount,
            'refund_reason'   => 'test',
            'refund_date'     => now(),
            'refund_method'   => 'cash',
            'approval_status' => $status,
            'branch_id'       => $this->branch->id,
            '_who_added'      => $this->admin->id,
        ]);
    }

    /** @test */
    public function 账实相符时报告干净并返回0(): void
    {
        $invoice = $this->makeInvoice(1000, 400);
        $this->addPayment($invoice, 400);

        $this->artisan('invoices:check-paid-drift')
            ->expectsOutputToContain('Drifting rows : 0')
            ->expectsOutputToContain('Safe to deploy')
            ->assertExitCode(0);
    }

    /**
     * 有已付金额、无收款明细 —— 重算会把它抹平，正是要在上线前捞出来的那种。
     */
    /** @test */
    public function 存储金额高于明细时报出并返回非0(): void
    {
        $this->makeInvoice(1000, 300);

        $this->artisan('invoices:check-paid-drift')
            ->expectsOutputToContain('Drifting rows : 1')
            ->expectsOutputToContain('lose recorded money on recompute')
            ->assertExitCode(1);
    }

    /** @test */
    public function 明细高于存储金额时也报出(): void
    {
        $invoice = $this->makeInvoice(1000, 0);
        $this->addPayment($invoice, 500);

        $this->artisan('invoices:check-paid-drift')
            ->expectsOutputToContain('Drifting rows : 1')
            ->assertExitCode(1);
    }

    /**
     * 退费不产生收款行，命令必须把已通过的退费算进去，否则每张退过费的账单
     * 都会被误报成漂移。
     */
    /** @test */
    public function 已通过的退费不算漂移(): void
    {
        $invoice = $this->makeInvoice(1000, 600);
        $this->addPayment($invoice, 1000);
        $this->addRefund($invoice, 400, Refund::APPROVAL_APPROVED);

        $this->artisan('invoices:check-paid-drift')
            ->expectsOutputToContain('Drifting rows : 0')
            ->assertExitCode(0);
    }

    /** @test */
    public function 待审批的退费不参与抵扣(): void
    {
        $invoice = $this->makeInvoice(1000, 1000);
        $this->addPayment($invoice, 1000);
        $this->addRefund($invoice, 400, Refund::APPROVAL_PENDING);

        // 钱还没退出去，paid_amount 就该是 1000，不算漂移
        $this->artisan('invoices:check-paid-drift')
            ->expectsOutputToContain('Drifting rows : 0')
            ->assertExitCode(0);
    }

    /** @test */
    public function 撤销的收款不计入明细(): void
    {
        $invoice = $this->makeInvoice(1000, 300);
        $this->addPayment($invoice, 300);
        $this->addPayment($invoice, 500)->delete();

        $this->artisan('invoices:check-paid-drift')
            ->expectsOutputToContain('Drifting rows : 0')
            ->assertExitCode(0);
    }

    /** @test */
    public function 软删的账单不参与体检(): void
    {
        $this->makeInvoice(1000, 300)->delete();

        $this->artisan('invoices:check-paid-drift')
            ->expectsOutputToContain('No invoices found')
            ->assertExitCode(0);
    }

    /** @test */
    public function 分以下的尾数差不算漂移(): void
    {
        $invoice = $this->makeInvoice(1000, 300.001);
        $this->addPayment($invoice, 300);

        $this->artisan('invoices:check-paid-drift')
            ->expectsOutputToContain('Drifting rows : 0')
            ->assertExitCode(0);
    }

    /** @test */
    public function json输出包含逐条明细(): void
    {
        $this->makeInvoice(1000, 300);

        $this->artisan('invoices:check-paid-drift --json')->assertExitCode(1);

        // 单独跑一次取输出内容做结构断言
        \Illuminate\Support\Facades\Artisan::call('invoices:check-paid-drift', ['--json' => true]);
        $payload = json_decode(\Illuminate\Support\Facades\Artisan::output(), true);

        $this->assertSame(1, $payload['drift_count']);
        $this->assertSame('300.00', $payload['rows'][0]['stored_paid']);
        $this->assertSame('0.00', $payload['rows'][0]['expected_paid']);
        $this->assertSame('-300.00', $payload['rows'][0]['delta']);
    }

    /** @test */
    public function 命令是只读的(): void
    {
        $invoice = $this->makeInvoice(1000, 300);

        $this->artisan('invoices:check-paid-drift')->assertExitCode(1);

        $this->assertEquals(300, $invoice->fresh()->paid_amount, '体检命令不得改动任何数据');
        $this->assertSame(0, InvoicePayment::count());
    }
}
