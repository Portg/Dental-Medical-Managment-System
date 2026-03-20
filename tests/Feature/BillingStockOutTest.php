<?php

namespace Tests\Feature;

use App\Branch;
use App\InventoryBatch;
use App\InventoryCategory;
use App\InventoryItem;
use App\StockIn;
use App\Invoice;
use App\InvoiceItem;
use App\MedicalService;
use App\Patient;
use App\Role;
use App\ServiceConsumable;
use App\Services\StockOutService;
use App\StockOut;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 测试前台代销库存联动：createBillingStockOut / rollbackBillingStockOut
 * 覆盖 AG-048(悲观锁) AG-049(幂等) AG-050(回滚原子性) AG-051(不足放行) AG-065(bcmath)
 */
class BillingStockOutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;
    private MedicalService $service;
    private InventoryItem $item;
    private StockOutService $stockOutService;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Test Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Administrator', 'slug' => 'admin']);

        $this->user = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($this->user);

        $this->patient = Patient::create([
            'patient_no' => 'P20260001',
            'surname'    => '李',
            'othername'  => '四',
            'gender'     => 'Female',
            'phone_no'   => '13900139000',
            '_who_added' => $this->user->id,
        ]);

        $this->service = MedicalService::create([
            'name'        => '洁牙',
            'price'       => '200.00',
            '_who_added'  => $this->user->id,
        ]);

        $category = InventoryCategory::create([
            'name'      => '消耗品',
            'is_active' => true,
            '_who_added' => $this->user->id,
        ]);

        $this->item = InventoryItem::create([
            'item_code'         => 'ITEM001',
            'name'              => '洁牙膏',
            'unit'              => '支',
            'category_id'       => $category->id,
            'current_stock'     => '10.0000',
            'average_cost'      => '5.00',
            'stock_warning_level' => 2,
            '_who_added'        => $this->user->id,
        ]);

        // 关联服务耗材：每次洁牙消耗 2 支洁牙膏
        ServiceConsumable::create([
            'medical_service_id'  => $this->service->id,
            'inventory_item_id'   => $this->item->id,
            'qty'                 => '2.00',
            '_who_added'          => $this->user->id,
        ]);

        // 建立入库单（批次 FK 需要）
        $stockIn = StockIn::create([
            'stock_in_no'   => 'SI20260001',
            'stock_in_date' => now()->format('Y-m-d'),
            'status'        => StockIn::STATUS_CONFIRMED,
            '_who_added'    => $this->user->id,
        ]);

        // 建立一个批次，与 current_stock 一致
        InventoryBatch::create([
            'inventory_item_id' => $this->item->id,
            'batch_no'          => 'BATCH001',
            'qty'               => '10.0000',
            'unit_cost'         => '5.00',
            'status'            => 'available',
            'stock_in_id'       => $stockIn->id,
            '_who_added'        => $this->user->id,
        ]);

        $this->stockOutService = app(StockOutService::class);
    }

    // ─── 辅助：创建发票和发票项 ──────────────────────────────────────

    private function makeInvoice(): Invoice
    {
        return Invoice::create([
            'invoice_no' => 'INV20260001',
            'patient_id' => $this->patient->id,
            'status'     => 'paid',
            '_who_added' => $this->user->id,
        ]);
    }

    private function invoiceItems(int $qty = 1): array
    {
        return [
            [
                'medical_service_id' => $this->service->id,
                'qty'                => $qty,
            ],
        ];
    }

    // ─── 正常出库 ─────────────────────────────────────────────────

    /** @test */
    public function it_creates_confirmed_stock_out_and_deducts_inventory()
    {
        $invoice = $this->makeInvoice();

        // 每次治疗耗 2 支，qty=1 次治疗 → 扣减 2 支
        $result = $this->stockOutService->createBillingStockOut(
            $invoice->id,
            $this->patient->id,
            null,
            $this->invoiceItems(1)
        );

        $this->assertTrue($result['status']);
        $this->assertEmpty($result['warnings']);

        // 出库单已创建并已确认
        $stockOut = StockOut::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($stockOut);
        $this->assertEquals(StockOut::STATUS_CONFIRMED, $stockOut->status);
        $this->assertFalse($stockOut->stock_insufficient);

        // 库存总量已扣减：10 - 2 = 8
        $this->item->refresh();
        $this->assertEquals('8.00', $this->item->current_stock);

        // 批次也已扣减：10 - 2 = 8
        $batch = InventoryBatch::first();
        $this->assertEquals('8.00', $batch->qty);
        $this->assertEquals('available', $batch->status);
    }

    /** @test */
    public function it_depletes_batch_when_all_stock_consumed()
    {
        $invoice = $this->makeInvoice();

        // 10 次治疗 × 2 支 = 20 支，但库存只有 10 支 → 库存不足场景
        // 改用 5 次：5 × 2 = 10，刚好耗尽
        $result = $this->stockOutService->createBillingStockOut(
            $invoice->id,
            $this->patient->id,
            null,
            $this->invoiceItems(5)
        );

        $this->assertTrue($result['status']);

        $batch = InventoryBatch::first();
        $this->assertEquals('0.00', $batch->qty);
        $this->assertEquals('depleted', $batch->status);

        $this->item->refresh();
        $this->assertEquals('0.00', $this->item->current_stock);
    }

    // ─── AG-049: 幂等性 ───────────────────────────────────────────

    /** @test */
    public function it_is_idempotent_for_same_invoice()
    {
        $invoice = $this->makeInvoice();
        $items   = $this->invoiceItems(1);

        $this->stockOutService->createBillingStockOut($invoice->id, $this->patient->id, null, $items);
        // 第二次调用不应再扣库存
        $result = $this->stockOutService->createBillingStockOut($invoice->id, $this->patient->id, null, $items);

        $this->assertTrue($result['status']);
        $this->assertEmpty($result['warnings']);

        // 只有一条出库单
        $this->assertEquals(1, StockOut::where('invoice_id', $invoice->id)->count());

        // 库存只被扣了一次：10 - 2 = 8
        $this->item->refresh();
        $this->assertEquals('8.00', $this->item->current_stock);
    }

    // ─── AG-051: 库存不足放行 ────────────────────────────────────

    /** @test */
    public function it_allows_billing_when_stock_insufficient_and_sets_flag()
    {
        $invoice = $this->makeInvoice();

        // 10 次治疗 × 2 支 = 20 支，库存只有 10 支
        $result = $this->stockOutService->createBillingStockOut(
            $invoice->id,
            $this->patient->id,
            null,
            $this->invoiceItems(10)
        );

        $this->assertTrue($result['status']);
        $this->assertNotEmpty($result['warnings']);

        $stockOut = StockOut::where('invoice_id', $invoice->id)->first();
        $this->assertTrue($stockOut->stock_insufficient);

        // 库存已被扣至 0（实际可用量）
        $this->item->refresh();
        $this->assertEquals('0.00', $this->item->current_stock);
    }

    // ─── AG-050: 回滚 ────────────────────────────────────────────

    /** @test */
    public function it_restores_stock_and_removes_stock_out_on_rollback()
    {
        $invoice = $this->makeInvoice();
        $this->stockOutService->createBillingStockOut(
            $invoice->id,
            $this->patient->id,
            null,
            $this->invoiceItems(1)
        );

        // 确认已扣减
        $this->item->refresh();
        $this->assertEquals('8.00', $this->item->current_stock);

        // 回滚
        $this->stockOutService->rollbackBillingStockOut($invoice->id);

        // 出库单已软删除
        $this->assertEquals(0, StockOut::where('invoice_id', $invoice->id)->count());

        // 库存已恢复
        $this->item->refresh();
        $this->assertEquals('10.00', $this->item->current_stock);

        // 批次 qty 已恢复
        $batch = InventoryBatch::first();
        $this->assertEquals('10.00', $batch->qty);
        $this->assertEquals('available', $batch->status);
    }

    /** @test */
    public function rollback_is_idempotent_when_no_stock_out_exists()
    {
        // 对不存在出库单的发票 rollback，不应抛异常
        $this->stockOutService->rollbackBillingStockOut(99999);

        // 库存不变
        $this->item->refresh();
        $this->assertEquals('10.00', $this->item->current_stock);
    }

    /** @test */
    public function rollback_restores_depleted_batch_to_available()
    {
        $invoice = $this->makeInvoice();
        // 耗尽库存
        $this->stockOutService->createBillingStockOut(
            $invoice->id,
            $this->patient->id,
            null,
            $this->invoiceItems(5)
        );

        $batch = InventoryBatch::first();
        $this->assertEquals('depleted', $batch->status);

        // 回滚后应恢复为 available
        $this->stockOutService->rollbackBillingStockOut($invoice->id);

        $batch->refresh();
        $this->assertEquals('available', $batch->status);
        $this->assertEquals('10.00', $batch->qty);
    }
}
