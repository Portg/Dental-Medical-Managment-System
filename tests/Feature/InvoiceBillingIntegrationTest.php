<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\InventoryBatch;
use App\InventoryCategory;
use App\InventoryItem;
use App\MedicalService;
use App\Patient;
use App\Role;
use App\ServiceConsumable;
use App\Services\InvoiceService;
use App\StockIn;
use App\StockOut;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P0 端到端集成测试：Invoice+库存 / Invoice追加+库存(AG-069) / 删除+回滚
 *
 * 与 BillingStockOutTest 的区别：本测试通过 InvoiceService 调用，
 * 验证 InvoiceService ↔ StockOutService 的集成契约。
 */
class InvoiceBillingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;
    private MedicalService $service;
    private InventoryItem $item;
    private InvoiceService $invoiceService;

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
            'surname'    => '王',
            'othername'  => '五',
            'gender'     => 'Female',
            'phone_no'   => '13800138001',
            '_who_added' => $this->user->id,
        ]);

        $this->service = MedicalService::create([
            'name'       => '根管治疗',
            'price'      => '500.00',
            '_who_added' => $this->user->id,
        ]);

        $category = InventoryCategory::create([
            'name'       => '消耗品',
            'is_active'  => true,
            '_who_added' => $this->user->id,
        ]);

        $this->item = InventoryItem::create([
            'item_code'           => 'ITEM001',
            'name'                => '根管锉',
            'unit'                => '支',
            'category_id'         => $category->id,
            'current_stock'       => '20.0000',
            'average_cost'        => '10.00',
            'stock_warning_level' => 2,
            '_who_added'          => $this->user->id,
        ]);

        // 每次根管治疗消耗 3 支根管锉
        ServiceConsumable::create([
            'medical_service_id' => $this->service->id,
            'inventory_item_id'  => $this->item->id,
            'qty'                => '3.00',
            '_who_added'         => $this->user->id,
        ]);

        $stockIn = StockIn::create([
            'stock_in_no'   => 'SI20260001',
            'stock_in_date' => now()->format('Y-m-d'),
            'status'        => StockIn::STATUS_CONFIRMED,
            '_who_added'    => $this->user->id,
        ]);

        InventoryBatch::create([
            'inventory_item_id' => $this->item->id,
            'batch_no'          => 'BATCH001',
            'qty'               => '20.0000',
            'unit_cost'         => '10.00',
            'status'            => 'available',
            'stock_in_id'       => $stockIn->id,
            '_who_added'        => $this->user->id,
        ]);

        $this->invoiceService = app(InvoiceService::class);
    }

    // ─── 辅助：构造账单 items ───────────────────────────────────

    private function billingItems(int $qty = 1): array
    {
        $price = '500.00';
        return [
            [
                'medical_service_id' => $this->service->id,
                'qty'                => $qty,
                'price'              => $price,
                'discount_rate'      => 100,
                'discounted_price'   => bcmul($price, (string) $qty, 2),
                'actual_paid'        => bcmul($price, (string) $qty, 2),
                'arrears'            => '0.00',
                'doctor_id'          => $this->user->id,
            ],
        ];
    }

    // =========================================================================
    // P0-1: Invoice+库存 — createBillingInvoice 触发 createBillingStockOut
    // =========================================================================

    /** @test */
    public function create_billing_invoice_auto_deducts_stock(): void
    {
        // qty=2 次治疗，每次耗 3 支 → 预期扣减 6 支，剩余 14 支
        $result = $this->invoiceService->createBillingInvoice(
            $this->patient->id,
            $this->billingItems(2),
            [],
            100,
            null,
            'front_desk'
        );

        $this->assertTrue($result['status'], $result['message'] ?? '');
        $invoiceId = $result['invoice_id'];

        // 出库单已自动创建
        $stockOut = StockOut::where('invoice_id', $invoiceId)->first();
        $this->assertNotNull($stockOut, '出库单未创建');
        $this->assertEquals(StockOut::STATUS_CONFIRMED, $stockOut->status);

        // 库存已扣减：20 - 6 = 14
        $this->item->refresh();
        $this->assertEquals('14.00', $this->item->current_stock);

        // 批次也已扣减
        $batch = InventoryBatch::first();
        $this->assertEquals('14.00', $batch->qty);
    }

    /** @test */
    public function create_billing_invoice_returns_warning_when_stock_insufficient(): void
    {
        // qty=7 次治疗 × 3 支 = 21 支，库存只有 20 支 → 允许收费，stock_insufficient=true
        $result = $this->invoiceService->createBillingInvoice(
            $this->patient->id,
            $this->billingItems(7),
            [],
            100,
            null,
            'front_desk'
        );

        // Invoice 创建成功
        $this->assertTrue($result['status'], $result['message'] ?? '');

        // 出库单已标记库存不足
        $stockOut = StockOut::where('invoice_id', $result['invoice_id'])->first();
        $this->assertNotNull($stockOut);
        $this->assertTrue($stockOut->stock_insufficient, '库存不足标记应为 true');

        // 库存已扣至 0
        $this->item->refresh();
        $this->assertEquals('0.00', $this->item->current_stock);
    }

    // =========================================================================
    // P0-2: Invoice追加+库存 (AG-069) — createInvoice 触发 appendBillingStockOut
    // =========================================================================

    /** @test */
    public function ag069_append_invoice_items_deducts_stock_incrementally(): void
    {
        $appointment = Appointment::create([
            'appointment_no' => 'APT20260001',
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->user->id,
            'start_date'     => now()->format('Y-m-d'),
            'status'         => Appointment::STATUS_IN_PROGRESS,
            '_who_added'     => $this->user->id,
        ]);

        $appendItems = [
            [
                'medical_service_id' => $this->service->id,
                'qty'                => 1,
                'price'              => '500.00',
                'doctor_id'          => $this->user->id,
            ],
        ];

        // 第一次追加：qty=1 × 3支 = 3支
        $result1 = $this->invoiceService->createInvoice($appointment->id, $appendItems);
        $this->assertTrue($result1['status'], $result1['message'] ?? '');

        $this->item->refresh();
        $this->assertEquals('17.00', $this->item->current_stock, '首次追加后库存应为 17');

        // 第二次追加到同一 appointment（同一 invoice）
        $result2 = $this->invoiceService->createInvoice($appointment->id, $appendItems);
        $this->assertTrue($result2['status'], $result2['message'] ?? '');

        $this->item->refresh();
        $this->assertEquals('14.00', $this->item->current_stock, '二次追加后库存应为 14');

        // 整个 appointment 只有一条出库单
        $invoice = \App\Invoice::where('appointment_id', $appointment->id)->firstOrFail();
        $this->assertEquals(1, StockOut::where('invoice_id', $invoice->id)->count(), '同一发票只应有一条出库单');
    }

    // =========================================================================
    // P0-3: 删除+回滚 — deleteInvoice 原子性：库存恢复 + 发票软删除
    // =========================================================================

    /** @test */
    public function delete_invoice_rolls_back_stock_and_soft_deletes_invoice(): void
    {
        $result = $this->invoiceService->createBillingInvoice(
            $this->patient->id,
            $this->billingItems(2),
            [],
            100,
            null,
            'front_desk'
        );

        $this->assertTrue($result['status']);
        $invoiceId = $result['invoice_id'];

        // 确认库存已扣减
        $this->item->refresh();
        $this->assertEquals('14.00', $this->item->current_stock);

        // 执行删除
        $deleteResult = $this->invoiceService->deleteInvoice($invoiceId);
        $this->assertTrue($deleteResult['status'], $deleteResult['message'] ?? '');

        // 发票已软删除
        $this->assertSoftDeleted('invoices', ['id' => $invoiceId]);

        // 出库单已软删除
        $this->assertEquals(0, StockOut::where('invoice_id', $invoiceId)->count(), '出库单应已软删除');

        // 库存已完整恢复
        $this->item->refresh();
        $this->assertEquals('20.00', $this->item->current_stock, '库存应恢复原值');

        $batch = InventoryBatch::first();
        $this->assertEquals('20.00', $batch->qty, '批次 qty 应恢复原值');
        $this->assertEquals('available', $batch->status, '批次状态应恢复 available');
    }

    /** @test */
    public function delete_invoice_without_stock_out_only_soft_deletes_invoice(): void
    {
        // 直接创建一张没有触发库存扣减的发票（无 ServiceConsumable 关联的服务）
        $plainService = MedicalService::create([
            'name'       => '咨询费',
            'price'      => '100.00',
            '_who_added' => $this->user->id,
        ]);

        $result = $this->invoiceService->createBillingInvoice(
            $this->patient->id,
            [
                [
                    'medical_service_id' => $plainService->id,
                    'qty'                => 1,
                    'price'              => '100.00',
                    'discount_rate'      => 100,
                    'discounted_price'   => '100.00',
                    'actual_paid'        => '100.00',
                    'arrears'            => '0.00',
                    'doctor_id'          => $this->user->id,
                ],
            ],
            [],
            100,
            null,
            'front_desk'
        );

        $this->assertTrue($result['status']);
        $invoiceId = $result['invoice_id'];

        // 该服务无 ServiceConsumable，不会产生出库单
        $this->assertEquals(0, StockOut::where('invoice_id', $invoiceId)->count());

        // 删除不应抛异常
        $deleteResult = $this->invoiceService->deleteInvoice($invoiceId);
        $this->assertTrue($deleteResult['status']);
        $this->assertSoftDeleted('invoices', ['id' => $invoiceId]);

        // 库存不受影响
        $this->item->refresh();
        $this->assertEquals('20.00', $this->item->current_stock);
    }
}
