<?php

namespace Tests\Feature;

use App\Branch;
use App\Invoice;
use App\Patient;
use App\Role;
use App\Services\InvoicePaymentService;
use App\Services\TodayWorkService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * 日终对账表上的支付方式必须是中文。
 *
 * 库里存的是 'Cash' / 'Self Account' 这类英文枚举，直出到界面上就是一行英文 ——
 * 前台每天都要看这张表核账。映射本来就在 InvoicePaymentService::methodLabel() 里，
 * 只是对账与「今日已收款」两处没接上。
 */
class TodayWorkReconcileTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private Invoice $invoice;

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

        $patient = Patient::create([
            'patient_no' => '20260801',
            'surname'    => '钱',
            'othername'  => '七',
            'gender'     => 'Male',
            'phone_no'   => '13800138007',
            '_who_added' => $this->cashier->id,
        ]);

        $this->invoice = Invoice::create([
            'invoice_no'   => 'INV-RECON-1',
            'invoice_date' => now()->format('Y-m-d'),
            'total_amount' => 1000,
            'paid_amount'  => 0,
            'patient_id'   => $patient->id,
            'branch_id'    => $branch->id,
            '_who_added'   => $this->cashier->id,
        ]);
    }

    private function pay(string $method, float $amount): void
    {
        app(InvoicePaymentService::class)->createPayment([
            'amount'         => $amount,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => $method,
            'invoice_id'     => $this->invoice->id,
        ]);
    }

    /** @test */
    public function 对账按支付方式汇总时显示中文(): void
    {
        $this->pay('Cash', 200);
        $this->pay('WeChat', 300);

        $data = app(TodayWorkService::class)->getTodayBilling($this->cashier->branch_id, now()->format('Y-m-d'));

        $methods = array_column($data['by_method'], 'method');

        $this->assertContains('现金', $methods, '对账表的支付方式应当是中文');
        $this->assertContains('微信支付', $methods);
        $this->assertNotContains('Cash', $methods, '不该再直出英文枚举');
        $this->assertNotContains('WeChat', $methods);

        // 金额汇总不受影响
        $this->assertEquals(500, $data['total_amount']);
        $this->assertSame(2, $data['total_count']);
    }

    /** @test */
    public function 今日已收款列表的支付方式也是中文(): void
    {
        $this->pay('Self Account', 150);

        $data = app(TodayWorkService::class)->getTodayPayments($this->cashier->branch_id, now()->format('Y-m-d'));

        $this->assertNotEmpty($data['items']);
        $this->assertSame('自付账户', $data['items'][0]['payment_method']);
    }

    /**
     * 认不出的值原样返回 —— 服务商回执或历史数据可能带来枚举外的字符串，
     * 显示原值也好过显示空白。
     */
    /** @test */
    public function 枚举外的值原样显示而不是空白(): void
    {
        $this->assertSame('SomeGateway', InvoicePaymentService::methodLabel('SomeGateway'));
        $this->assertSame('-', InvoicePaymentService::methodLabel(null));
    }
}
