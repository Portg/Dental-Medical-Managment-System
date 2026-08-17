<?php

namespace Tests\Feature;

use App\Branch;
use App\Invoice;
use App\InvoicePayment;
use App\Patient;
use App\Role;
use App\Services\InvoicePaymentService;
use App\Services\TodayWorkService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * 员工收费明细：谁收了多少钱、收的是哪种钱。
 *
 * 「按支付方式汇总」只答得出「今天现金 1200」，答不出「这 1200 谁收的」。
 * 现金是唯一无痕的支付方式，交班点钞对不上时，没有这张表就无从追溯到人。
 * 这也是「不禁止医生收现金、靠明细可追溯」这条路线成立的前提。
 *
 * 这组用例钉四件事：
 *   1. 矩阵按员工分行、按支付方式分列，钱记在真正的收款人名下
 *   2. 两张表口径一致 —— 明细总计必须等于按支付方式汇总的总计
 *   3. 撤销（软删）的收款不计入，否则撤一笔账面就虚高
 *   4. 当天没出现过的支付方式不占列
 */
class StaffCollectionDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $frontDesk;
    private User $doctor;
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Administrator', 'slug' => 'admin']);

        $this->frontDesk = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $branch->id, 'status' => User::STATUS_ACTIVE,
            'surname' => '李', 'othername' => '前台',
        ]);
        $this->doctor = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $branch->id, 'status' => User::STATUS_ACTIVE,
            'surname' => '张', 'othername' => '医生',
        ]);

        Auth::login($this->frontDesk);

        $patient = Patient::create([
            'patient_no' => '20260901', 'surname' => '孙', 'othername' => '九',
            'gender' => 'Male', 'phone_no' => '13800138009', '_who_added' => $this->frontDesk->id,
        ]);

        $this->invoice = Invoice::create([
            'invoice_no' => 'INV-STAFF-1', 'invoice_date' => now()->format('Y-m-d'),
            'total_amount' => 5000, 'paid_amount' => 0,
            'patient_id' => $patient->id, 'branch_id' => $branch->id,
            '_who_added' => $this->frontDesk->id,
        ]);
    }

    /**
     * 以 $who 的身份收一笔 —— 收款人取 Auth::id()，所以必须真的切换登录身份，
     * 不能只改 _who_added，否则测不出「记在谁名下」这件事。
     */
    private function payAs(User $who, string $method, float $amount): InvoicePayment
    {
        Auth::login($who);

        return app(InvoicePaymentService::class)->createPayment([
            'amount'         => $amount,
            'payment_date'   => now()->format('Y-m-d'),
            'payment_method' => $method,
            'invoice_id'     => $this->invoice->id,
        ]);
    }

    private function detail(): array
    {
        return app(TodayWorkService::class)
            ->getTodayBilling($this->frontDesk->branch_id, now()->format('Y-m-d'))['staff_detail'];
    }

    private function rowFor(array $detail, string $name): array
    {
        foreach ($detail['rows'] as $row) {
            if (str_contains($row['staff_name'], $name)) {
                return $row;
            }
        }
        $this->fail("明细里找不到收款人「{$name}」");
    }

    /** @test */
    public function 明细按员工分行且钱记在真正的收款人名下(): void
    {
        $this->payAs($this->frontDesk, 'Cash', 200);
        $this->payAs($this->frontDesk, 'WeChat', 300);
        $this->payAs($this->doctor, 'Cash', 100);

        $detail = $this->detail();

        $this->assertCount(2, $detail['rows'], '两个人收过款就该有两行');

        $front = $this->rowFor($detail, '前台');
        $this->assertEquals(200, $front['amounts']['Cash']);
        $this->assertEquals(300, $front['amounts']['WeChat']);
        $this->assertEquals(500, $front['total']);

        $doctor = $this->rowFor($detail, '医生');
        $this->assertEquals(100, $doctor['amounts']['Cash']);
        $this->assertEquals(0, $doctor['amounts']['WeChat'], '医生没收微信，格子应当是 0 而不是缺键');
        $this->assertSame(2, $front['count'], '前台收了两笔（现金 + 微信）');
    }

    /** @test */
    public function 现金能追溯到具体收款人(): void
    {
        $this->payAs($this->doctor, 'Cash', 800);

        $detail = $this->detail();
        $doctorRow = $this->rowFor($detail, '医生');

        // 这条断言就是整张表存在的理由：交班点钞少 800，能指到人
        $this->assertEquals(800, $doctorRow['amounts']['Cash']);
        $this->assertSame($this->doctor->id, $doctorRow['staff_id']);
    }

    /** @test */
    public function 明细总计与按支付方式汇总的总计一致(): void
    {
        $this->payAs($this->frontDesk, 'Cash', 150);
        $this->payAs($this->doctor, 'WeChat', 250);
        $this->payAs($this->doctor, 'Cash', 75.5);

        $billing = app(TodayWorkService::class)
            ->getTodayBilling($this->frontDesk->branch_id, now()->format('Y-m-d'));

        $detailTotal = array_sum(array_column($billing['staff_detail']['rows'], 'total'));
        $detailCount = array_sum(array_column($billing['staff_detail']['rows'], 'count'));

        // 同一张对账页上的两个数字对不上，比少一张表更糟
        $this->assertEquals($billing['total_amount'], round($detailTotal, 2));
        $this->assertSame((int) $billing['total_count'], $detailCount);
    }

    /** @test */
    public function 撤销的收款不计入明细(): void
    {
        $payment = $this->payAs($this->frontDesk, 'Cash', 400);
        $this->payAs($this->frontDesk, 'Cash', 100);

        app(InvoicePaymentService::class)->deletePayment($payment->id);

        $front = $this->rowFor($this->detail(), '前台');

        $this->assertEquals(100, $front['amounts']['Cash'], '撤销的 400 不该还算在账上');
        $this->assertSame(1, $front['count']);
    }

    /**
     * 支付方式有十几种，全都列出来会把表撑爆且没有信息量。
     * 列只给当天真的收过的那几种。
     */
    /** @test */
    public function 当天没出现的支付方式不占列(): void
    {
        $this->payAs($this->frontDesk, 'Cash', 100);

        $keys = array_column($this->detail()['methods'], 'key');

        $this->assertSame(['Cash'], $keys);
        $this->assertNotContains('Cheque', $keys);
    }

    /** @test */
    public function 列头是中文且列序固定(): void
    {
        // 故意按与 PAYMENT_METHODS 相反的顺序录入
        $this->payAs($this->frontDesk, 'WeChat', 100);
        $this->payAs($this->frontDesk, 'Cash', 100);

        $methods = $this->detail()['methods'];
        $labels  = array_column($methods, 'label');

        $this->assertContains('现金', $labels);
        $this->assertContains('微信支付', $labels);
        $this->assertNotContains('Cash', $labels, '列头不该直出英文枚举');

        // 列序跟着 PAYMENT_METHODS 走，而不是跟着录入顺序 —— 否则换一天列序就变了，
        // 交班时容易看错列
        $order    = array_keys(InvoicePaymentService::PAYMENT_METHODS);
        $expected = array_values(array_intersect($order, array_column($methods, 'key')));
        $this->assertSame($expected, array_column($methods, 'key'));
    }

    /** @test */
    public function 无收款时返回空结构而不是报错(): void
    {
        $detail = $this->detail();

        $this->assertSame([], $detail['rows']);
        $this->assertSame([], $detail['methods']);
    }
}
