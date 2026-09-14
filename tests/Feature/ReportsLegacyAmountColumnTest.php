<?php

namespace Tests\Feature;

use App\Branch;
use App\Invoice;
use App\InvoiceItem;
use App\MedicalService;
use App\Patient;
use App\Role;
use App\Services\DebtorsReportService;
use App\Services\ProceduresReportService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 欠款报表与项目收入报表不能再读 invoice_items.amount。
 *
 * invoice_items.amount 是 2019 年的遗留列，两条开单路径（createInvoice 与
 * createBillingInvoice）都从未写它，MySQL 非严格模式下补 0。InvoiceItemService 与
 * InvoiceItemController 早就改用 COALESCE(actual_paid, discounted_price, price*qty)
 * 了，但这两张报表一直没跟上：
 *
 *   - 项目收入报表：sum(amount * qty) → 每个项目都算成 0
 *   - 欠款报表：outstanding = 0 - 已收 = 负数，再被 having outstanding > 0 整条滤掉，
 *     报表显示「没有欠款」—— 比算错更糟，是把欠款藏了
 *
 * 而 92f20f8 之后划价面板是工作台与患者页唯一的开单入口，也就是说这两张报表
 * 对所有新单都是失真的。
 */
class ReportsLegacyAmountColumnTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private Patient $patient;
    private MedicalService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Administrator', 'slug' => 'admin']);

        $this->staff = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $branch->id, 'status' => User::STATUS_ACTIVE,
        ]);

        $this->patient = Patient::create([
            'patient_no' => 'RPT-1', 'surname' => '刘', 'othername' => '万友',
            'gender' => 'Male', 'phone_no' => '13800138021', '_who_added' => $this->staff->id,
        ]);

        $this->service = MedicalService::create([
            'name' => '树脂补牙(中)', 'price' => 300, '_who_added' => $this->staff->id,
        ]);
    }

    /**
     * 按划价面板的写法造一张单：amount 那一列刻意不写，就是线上的样子。
     */
    private function makeInvoice(string $no, float $total, float $paid, int $qty = 1): Invoice
    {
        $invoice = Invoice::create([
            'invoice_no'   => $no,
            'patient_id'   => $this->patient->id,
            'invoice_date' => now()->format('Y-m-d'),
            'total_amount' => $total,
            'paid_amount'  => $paid,
            '_who_added'   => $this->staff->id,
        ]);

        InvoiceItem::create([
            'invoice_id'         => $invoice->id,
            'medical_service_id' => $this->service->id,
            'qty'                => $qty,
            'price'              => 300,
            'discount_rate'      => 100,
            // 行小计 —— 已经含数量，报表不该再乘一次 qty
            'discounted_price'   => $total,
            'actual_paid'        => $total,
            'arrears'            => 0,
            '_who_added'         => $this->staff->id,
        ]);

        return $invoice->fresh();
    }

    public function test_项目收入报表不会把划价开的单算成零(): void
    {
        $this->makeInvoice('RPT-INV-1', 300, 300);

        $rows = app(ProceduresReportService::class)
            ->getProceduresIncome(now()->subDay()->format('Y-m-d'), now()->addDay()->format('Y-m-d'));

        $this->assertCount(1, $rows);
        $this->assertSame('树脂补牙(中)', $rows[0]->name);
        $this->assertEquals(300, (float) $rows[0]->procedure_income, '读遗留列 amount 的话这里会是 0');
    }

    /**
     * actual_paid 存的是行小计（已含数量）。报表再乘一次 qty 的话，
     * 一次做两颗牙的项目会被按平方放大成 1200。
     */
    public function test_项目收入不会因为数量被重复相乘(): void
    {
        $this->makeInvoice('RPT-INV-2', 600, 600, qty: 2);

        $rows = app(ProceduresReportService::class)
            ->getProceduresIncome(now()->subDay()->format('Y-m-d'), now()->addDay()->format('Y-m-d'));

        $this->assertEquals(600, (float) $rows[0]->procedure_income, '行小计已含数量，不该再乘 qty');
    }

    /**
     * 赠送项目（实收 0）就该算 0，不该回退到原价。
     * 这条钉的是「用 COALESCE 而不是 NULLIF(x,0)」这个选择。
     */
    public function test_实收为零的赠送项目算零而不是原价(): void
    {
        $invoice = Invoice::create([
            'invoice_no' => 'RPT-INV-FREE', 'patient_id' => $this->patient->id,
            'invoice_date' => now()->format('Y-m-d'), 'total_amount' => 0, 'paid_amount' => 0,
            '_who_added' => $this->staff->id,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id, 'medical_service_id' => $this->service->id,
            'qty' => 1, 'price' => 300, 'discount_rate' => 0,
            'discounted_price' => 0, 'actual_paid' => 0, 'arrears' => 0,
            '_who_added' => $this->staff->id,
        ]);

        $rows = app(ProceduresReportService::class)
            ->getProceduresIncome(now()->subDay()->format('Y-m-d'), now()->addDay()->format('Y-m-d'));

        $this->assertEquals(0, (float) $rows[0]->procedure_income, '赠送项目算 0，不回退原价');
    }

    /**
     * 欠款报表要能看见欠款。改之前 outstanding = 0 - 已收 = 负数，
     * 被 having > 0 滤掉，这张单根本不出现。
     */
    public function test_欠款报表看得见划价开的欠款单(): void
    {
        $this->makeInvoice('RPT-INV-DEBT', 300, 100);   // 欠 200

        $rows = app(DebtorsReportService::class)->getDebtorsData();

        $this->assertCount(1, $rows, '欠款单没有出现在欠款报表里');
        $this->assertSame('RPT-INV-DEBT', $rows[0]['invoice_no']);
        $this->assertEquals(300, (float) $rows[0]['invoice_amount']);
        $this->assertEquals(100, (float) $rows[0]['amount_paid']);
        $this->assertEquals(200, (float) $rows[0]['outstanding_balance']);
    }

    public function test_已结清的单不进欠款报表(): void
    {
        $this->makeInvoice('RPT-INV-PAID', 300, 300);

        $this->assertSame([], app(DebtorsReportService::class)->getDebtorsData());
    }

    /**
     * 患者页开的单没有 appointment_id。原来只按 appointments.patient_id 找患者，
     * 这些单的姓名与电话是空的 —— 一张不知道是谁欠的欠款单没有用。
     */
    public function test_没有预约的单也认得出是哪位患者(): void
    {
        $this->makeInvoice('RPT-INV-NOAPT', 300, 0);

        $rows = app(DebtorsReportService::class)->getDebtorsData();

        $this->assertCount(1, $rows);
        $this->assertSame('刘', $rows[0]['surname']);
        $this->assertSame('13800138021', $rows[0]['phone_no']);
    }
}
