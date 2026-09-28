<?php

namespace Tests\Feature;

use App\Branch;
use App\Invoice;
use App\InvoiceItem;
use App\MedicalService;
use App\Patient;
use App\Permission;
use App\PrepaidItemUsage;
use App\Role;
use App\RolePermission;
use App\Services\PrepaidItemService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 剩余项目 —— 已收费但还没做完的那部分。
 *
 * 补的是一个财务上的洞：预收款收进来了、服务还欠着，而系统里原先只有「计划」
 * 和「已收钱」两头，中间没有东西回答「这个患者交的钱里还有多少没兑现」。
 *
 * 这组用例盯三件事：
 *   - 口径：只有勾了「按次核销」的项目才有余量，普通收费行不进来
 *   - 算账：余量由收费行数量减核销流水算出，不存字段
 *   - 不能透支：核销不能超过余量，并发点两次也不行
 */
class PrepaidItemTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $viewer;
    private Patient $patient;
    private MedicalService $cardService;
    private MedicalService $plainService;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::first() ?: Branch::create(['name' => 'Main Branch', 'is_active' => true]);

        $perms = [];
        foreach (['view-invoices', 'edit-invoices'] as $slug) {
            $perms[$slug] = Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $slug, 'module' => '账单管理']
            )->id;
        }

        $full = Role::create(['name' => 'Cashier', 'slug' => 'cashier-prepaid']);
        foreach ($perms as $permId) {
            RolePermission::create(['role_id' => $full->id, 'permission_id' => $permId]);
        }

        // 只能看余量、不能核销：核销是把服务兑现掉、直接减少诊所负债
        $readOnly = Role::create(['name' => 'Viewer', 'slug' => 'viewer-prepaid']);
        RolePermission::create(['role_id' => $readOnly->id, 'permission_id' => $perms['view-invoices']]);

        $this->staff = User::factory()->create([
            'role_id' => $full->id, 'branch_id' => $branch->id, 'status' => User::STATUS_ACTIVE,
        ]);
        $this->viewer = User::factory()->create([
            'role_id' => $readOnly->id, 'branch_id' => $branch->id, 'status' => User::STATUS_ACTIVE,
        ]);

        $this->patient = Patient::create([
            'patient_no' => 'PP-' . uniqid(),
            'surname'    => '马',
            'othername'  => '保蕾',
            'phone_no'   => '15233268089',
            '_who_added' => $this->staff->id,
        ]);

        $this->cardService = MedicalService::create([
            'name' => '洁牙次卡', 'unit' => '次', 'price' => 200,
            'is_active' => true, 'track_delivery' => true, '_who_added' => $this->staff->id,
        ]);
        $this->plainService = MedicalService::create([
            'name' => '补牙', 'unit' => '颗', 'price' => 280,
            'is_active' => true, 'track_delivery' => false, '_who_added' => $this->staff->id,
        ]);
    }

    /**
     * 造一笔「已收费」的行。actual_paid 是实收，arrears 是这一行还欠多少 ——
     * 两者分开，因为有欠费的权属并不是真「预收」。
     */
    private function bill(MedicalService $service, float $qty, float $unit, float $paidPerUnit = null): InvoiceItem
    {
        $paidPerUnit = $paidPerUnit ?? $unit;

        $invoice = Invoice::create([
            'invoice_no'     => 'INV-' . uniqid(),
            'patient_id'     => $this->patient->id,
            'invoice_date'   => date('Y-m-d'),
            'total_amount'   => $qty * $unit,
            'paid_amount'    => $qty * $paidPerUnit,
            'payment_status' => $paidPerUnit >= $unit ? 'paid' : 'partial',
            '_who_added'     => $this->staff->id,
        ]);

        return InvoiceItem::create([
            'invoice_id'         => $invoice->id,
            'medical_service_id' => $service->id,
            'qty'                => $qty,
            'price'              => $unit,
            'discounted_price'   => $qty * $unit,
            'actual_paid'        => $qty * $paidPerUnit,
            'arrears'            => $qty * ($unit - $paidPerUnit),
            'amount'             => $qty * $unit,
            '_who_added'         => $this->staff->id,
        ]);
    }

    private function service(): PrepaidItemService
    {
        return app(PrepaidItemService::class);
    }

    /** @test */
    public function 只有按次核销的项目才进剩余项目(): void
    {
        $this->bill($this->cardService, 4, 200);
        $this->bill($this->plainService, 1, 280);   // 普通项目：当天做完了，不欠服务

        $rows = $this->service()->getRemainingForPatient($this->patient->id);

        $this->assertCount(1, $rows);
        $this->assertSame('洁牙次卡', $rows[0]['service_name']);
        $this->assertEquals(4, $rows[0]['remaining_qty']);
    }

    /** @test */
    public function 余量等于已购减去核销流水(): void
    {
        $item = $this->bill($this->cardService, 4, 200);

        $this->service()->consume($item->id, 1, [], $this->staff->id);
        $this->service()->consume($item->id, 1, [], $this->staff->id);

        $rows = $this->service()->getRemainingForPatient($this->patient->id);

        $this->assertEquals(2, $rows[0]['used_qty']);
        $this->assertEquals(2, $rows[0]['remaining_qty']);
        // 剩 2 次 × 每次实收 200
        $this->assertEquals(400.0, $rows[0]['prepaid_value']);
    }

    /** @test */
    public function 用完的权属不再出现在列表里(): void
    {
        $item = $this->bill($this->cardService, 2, 200);

        $this->service()->consume($item->id, 2, [], $this->staff->id);

        $this->assertCount(0, $this->service()->getRemainingForPatient($this->patient->id));
    }

    /** @test */
    public function 核销不能超过余量(): void
    {
        $item = $this->bill($this->cardService, 2, 200);

        $result = $this->service()->consume($item->id, 3, [], $this->staff->id);

        $this->assertFalse($result['success']);
        $this->assertSame(0, PrepaidItemUsage::count());
    }

    /**
     * 正好用完最后一次不能被判成超额 —— qty 是 decimal，浮点累加后
     * 3 - 3 可能是 -4e-16，直接比大小会把这一下拦掉。
     */
    /** @test */
    public function 正好用完最后一次能通过(): void
    {
        $item = $this->bill($this->cardService, 3, 200);

        $this->service()->consume($item->id, 1, [], $this->staff->id);
        $this->service()->consume($item->id, 1, [], $this->staff->id);
        $result = $this->service()->consume($item->id, 1, [], $this->staff->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['remaining_qty']);
    }

    /**
     * 两个前台同时点「用一次」，各自读到「还剩 1 次」然后都写入，
     * 余量就成了 -1。consume 里对收费行 lockForUpdate 把这件事串行化。
     */
    /** @test */
    public function 连续核销到零后再核销会被拦(): void
    {
        $item = $this->bill($this->cardService, 1, 200);

        $first  = $this->service()->consume($item->id, 1, [], $this->staff->id);
        $second = $this->service()->consume($item->id, 1, [], $this->staff->id);

        $this->assertTrue($first['success']);
        $this->assertFalse($second['success']);
        $this->assertSame(1, PrepaidItemUsage::count());
    }

    /** @test */
    public function 撤销核销会把余量加回去(): void
    {
        $item = $this->bill($this->cardService, 2, 200);
        $result = $this->service()->consume($item->id, 1, [], $this->staff->id);

        $this->service()->revoke($result['usage_id'], $this->staff->id);

        $rows = $this->service()->getRemainingForPatient($this->patient->id);
        $this->assertEquals(2, $rows[0]['remaining_qty']);
        // 软删除：撤销要留痕，不是把这条流水抹掉
        $this->assertSame(1, PrepaidItemUsage::withTrashed()->count());
    }

    /** @test */
    public function 欠费的权属会带上欠款金额(): void
    {
        // 买 2 次共 400，只付了 300 —— 每次实收 150，还欠 100
        $this->bill($this->cardService, 2, 200, 150);

        $rows = $this->service()->getRemainingForPatient($this->patient->id);

        $this->assertEquals(100.0, $rows[0]['arrears']);
        // 应交付的服务价值按折后价算
        $this->assertEquals(400.0, $rows[0]['remaining_value']);
        // 负债口径只认已经收到的钱
        $this->assertEquals(300.0, $rows[0]['prepaid_value']);
    }

    /** @test */
    public function 退款的账单不再欠服务(): void
    {
        $item = $this->bill($this->cardService, 3, 200);
        Invoice::where('id', $item->invoice_id)->update(['payment_status' => 'refunded']);

        $this->assertCount(0, $this->service()->getRemainingForPatient($this->patient->id));
    }

    /** @test */
    public function 汇总把负债口径算对(): void
    {
        $a = $this->bill($this->cardService, 4, 200);
        $this->bill($this->cardService, 2, 100);
        $this->service()->consume($a->id, 1, [], $this->staff->id);

        $summary = $this->service()->getPatientSummary($this->patient->id);

        $this->assertSame(2, $summary['item_count']);
        $this->assertEquals(5, $summary['remaining_qty']);       // 3 + 2
        $this->assertEquals(800.0, $summary['prepaid_value']);   // 3×200 + 2×100
    }

    /** @test */
    public function 核销接口要编辑账单权限(): void
    {
        $item = $this->bill($this->cardService, 2, 200);

        $this->actingAs($this->viewer)
            ->postJson('/prepaid-items/' . $item->id . '/consume', ['qty' => 1])
            ->assertForbidden();

        // 但看得见余量
        $this->actingAs($this->viewer)
            ->getJson('/prepaid-items/patient/' . $this->patient->id)
            ->assertOk()
            ->assertJsonPath('data.0.remaining_qty', 2);
    }

    /** @test */
    public function 核销接口超额返回422(): void
    {
        $item = $this->bill($this->cardService, 1, 200);

        $this->actingAs($this->staff)
            ->postJson('/prepaid-items/' . $item->id . '/consume', ['qty' => 5])
            ->assertStatus(422)
            ->assertJsonPath('status', false);
    }

    /** @test */
    public function 核销流水记得下用在哪天和谁做的(): void
    {
        $item = $this->bill($this->cardService, 2, 200);

        $this->actingAs($this->staff)
            ->postJson('/prepaid-items/' . $item->id . '/consume', [
                'qty'     => 1,
                'used_at' => '2026-09-18',
                'notes'   => '第一次洁治',
            ])
            ->assertOk();

        $history = $this->actingAs($this->staff)
            ->getJson('/prepaid-items/' . $item->id . '/history')
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $history);
        $this->assertSame('2026-09-18', $history[0]['used_at']);
        $this->assertSame('第一次洁治', $history[0]['notes']);
    }

    /** @test */
    public function 普通收费行不能被核销(): void
    {
        $item = $this->bill($this->plainService, 1, 280);

        $result = $this->service()->consume($item->id, 1, [], $this->staff->id);

        $this->assertFalse($result['success']);
        $this->assertSame(0, PrepaidItemUsage::count());
    }
}
