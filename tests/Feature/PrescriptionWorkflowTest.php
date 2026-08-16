<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\Invoice;
use App\MedicalService;
use App\Patient;
use App\Prescription;
use App\PrescriptionItem;
use App\Role;
use App\Services\PrescriptionService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PrescriptionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Patient $patient;
    private MedicalService $service1;
    private MedicalService $service2;
    private PrescriptionService $prescriptionService;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Test Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Administrator', 'slug' => 'admin']);

        $this->admin = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $branch->id,
            'is_doctor' => 'yes',
            'password'  => bcrypt('password'),
        ]);

        Auth::login($this->admin);

        $this->patient = Patient::create([
            'patient_no' => '20260001',
            'surname'    => '张',
            'othername'  => '三',
            'gender'     => 'Male',
            'phone_no'   => '13800138000',
            '_who_added' => $this->admin->id,
        ]);

        $this->service1 = MedicalService::create([
            'name'            => '阿莫西林胶囊',
            'price'           => 25.50,
            'is_prescription' => true,
            'is_active'       => true,
            '_who_added'      => $this->admin->id,
        ]);

        $this->service2 = MedicalService::create([
            'name'            => '布洛芬片',
            'price'           => 15.00,
            'is_prescription' => true,
            'is_active'       => true,
            '_who_added'      => $this->admin->id,
        ]);

        $this->prescriptionService = new PrescriptionService();
    }

    // ─── AG-023: 已关联 Invoice 的处方不允许删除 ──────────────────────

    public function test_ag023_delete_prescription_without_invoice_succeeds(): void
    {
        $result = $this->prescriptionService->createPrescription(
            ['patient_id' => $this->patient->id],
            [['medical_service_id' => $this->service1->id, 'quantity' => 2]]
        );

        $this->assertTrue($result['status']);

        $deleteResult = $this->prescriptionService->deletePrescription($result['prescription_id']);
        $this->assertTrue($deleteResult['status']);
        $this->assertSoftDeleted('prescriptions', ['id' => $result['prescription_id']]);
    }

    public function test_ag023_delete_prescription_with_invoice_blocked(): void
    {
        // Use saveAndSettle to create prescription + invoice in one step
        $result = $this->prescriptionService->saveAndSettle(
            ['patient_id' => $this->patient->id],
            [['medical_service_id' => $this->service1->id, 'quantity' => 1]]
        );

        $this->assertTrue($result['status']);

        $deleteResult = $this->prescriptionService->deletePrescription($result['prescription_id']);
        $this->assertFalse($deleteResult['status']);
        $this->assertDatabaseHas('prescriptions', ['id' => $result['prescription_id'], 'deleted_at' => null]);
    }

    public function test_ag023_is_deletable_attribute(): void
    {
        $prescription = Prescription::create([
            'prescription_no' => Prescription::generatePrescriptionNo(),
            'patient_id'      => $this->patient->id,
            'doctor_id'       => $this->admin->id,
            'status'          => 'pending',
            '_who_added'      => $this->admin->id,
        ]);

        $this->assertTrue($prescription->is_deletable);

        // Create a real invoice to link
        $invoice = Invoice::create([
            'invoice_no'         => Invoice::InvoiceNo(),
            'patient_id'         => $this->patient->id,
            'subtotal'           => 25.50,
            'total_amount'       => 25.50,
            'paid_amount'        => 0,
            'outstanding_amount' => 25.50,
            'payment_status'     => 'unpaid',
            '_who_added'         => $this->admin->id,
        ]);
        $prescription->update(['invoice_id' => $invoice->id]);
        $prescription->refresh();

        $this->assertFalse($prescription->is_deletable);
    }

    // ─── AG-024: 结算幂等，不重复创建 Invoice ────────────────────────

    public function test_ag024_settle_pending_prescription_creates_invoice(): void
    {
        $result = $this->prescriptionService->createPrescription(
            ['patient_id' => $this->patient->id],
            [
                ['medical_service_id' => $this->service1->id, 'quantity' => 2],
                ['medical_service_id' => $this->service2->id, 'quantity' => 1],
            ]
        );

        $this->assertTrue($result['status']);

        $settleResult = $this->prescriptionService->settlePrescription($result['prescription_id']);
        $this->assertTrue($settleResult['status']);
        $this->assertArrayHasKey('invoice_id', $settleResult);

        $prescription = Prescription::find($result['prescription_id']);
        $this->assertEquals('filled', $prescription->status);
        $this->assertNotNull($prescription->invoice_id);
    }

    public function test_ag024_settle_already_settled_returns_error(): void
    {
        $result = $this->prescriptionService->createPrescription(
            ['patient_id' => $this->patient->id],
            [['medical_service_id' => $this->service1->id, 'quantity' => 1]]
        );

        // First settle
        $settleResult1 = $this->prescriptionService->settlePrescription($result['prescription_id']);
        $this->assertTrue($settleResult1['status']);

        // Second settle — should be idempotent (rejected)
        $settleResult2 = $this->prescriptionService->settlePrescription($result['prescription_id']);
        $this->assertFalse($settleResult2['status']);
    }

    public function test_ag024_save_and_settle_creates_prescription_and_invoice(): void
    {
        $result = $this->prescriptionService->saveAndSettle(
            ['patient_id' => $this->patient->id],
            [['medical_service_id' => $this->service1->id, 'quantity' => 3]]
        );

        $this->assertTrue($result['status']);
        $this->assertArrayHasKey('prescription_id', $result);
        $this->assertArrayHasKey('invoice_id', $result);

        $prescription = Prescription::find($result['prescription_id']);
        $this->assertEquals('filled', $prescription->status);
        $this->assertEquals($result['invoice_id'], $prescription->invoice_id);

        $this->assertDatabaseHas('invoices', ['id' => $result['invoice_id']]);
    }

    // ─── AG-025: 单价从 medical_services 后端获取 ────────────────────

    public function test_ag025_unit_price_from_backend_not_frontend(): void
    {
        $result = $this->prescriptionService->createPrescription(
            ['patient_id' => $this->patient->id],
            [
                [
                    'medical_service_id' => $this->service1->id,
                    'quantity'           => 2,
                    'unit_price'         => 9999.99, // frontend-supplied, should be ignored
                ],
            ]
        );

        $this->assertTrue($result['status']);

        $item = PrescriptionItem::where('prescription_id', $result['prescription_id'])->first();
        // Unit price should come from medical_services.price (25.50), not the frontend value
        $this->assertEquals('25.50', $item->unit_price);
    }

    public function test_ag025_drug_name_auto_filled_from_service(): void
    {
        $result = $this->prescriptionService->createPrescription(
            ['patient_id' => $this->patient->id],
            [
                [
                    'medical_service_id' => $this->service1->id,
                    'quantity'           => 1,
                ],
            ]
        );

        $this->assertTrue($result['status']);

        $item = PrescriptionItem::where('prescription_id', $result['prescription_id'])->first();
        $this->assertEquals('阿莫西林胶囊', $item->drug_name);
    }

    // ─── AG-026: 数量最小为 1 ─────────────────────────────────────────

    public function test_ag026_quantity_minimum_is_one(): void
    {
        $result = $this->prescriptionService->createPrescription(
            ['patient_id' => $this->patient->id],
            [
                [
                    'medical_service_id' => $this->service1->id,
                    'quantity'           => 0, // should be clamped to 1
                ],
            ]
        );

        $this->assertTrue($result['status']);

        $item = PrescriptionItem::where('prescription_id', $result['prescription_id'])->first();
        $this->assertEquals(1, $item->quantity);
    }

    public function test_ag026_negative_quantity_clamped_to_one(): void
    {
        $result = $this->prescriptionService->createPrescription(
            ['patient_id' => $this->patient->id],
            [
                [
                    'medical_service_id' => $this->service2->id,
                    'quantity'           => -5,
                ],
            ]
        );

        $this->assertTrue($result['status']);

        $item = PrescriptionItem::where('prescription_id', $result['prescription_id'])->first();
        $this->assertEquals(1, $item->quantity);
    }

    // ─── Settled prescription edit protection ─────────────────────────

    public function test_update_settled_prescription_blocked(): void
    {
        $result = $this->prescriptionService->saveAndSettle(
            ['patient_id' => $this->patient->id],
            [['medical_service_id' => $this->service1->id, 'quantity' => 1]]
        );

        $this->assertTrue($result['status']);

        $updateResult = $this->prescriptionService->updatePrescription(
            $result['prescription_id'],
            ['notes' => 'changed'],
            [['medical_service_id' => $this->service2->id, 'quantity' => 5]]
        );

        $this->assertFalse($updateResult['status']);
    }

    // ─── Total amount calculation (bcmath) ────────────────────────────

    public function test_total_amount_uses_bcmath(): void
    {
        $result = $this->prescriptionService->createPrescription(
            ['patient_id' => $this->patient->id],
            [
                ['medical_service_id' => $this->service1->id, 'quantity' => 3], // 25.50 * 3 = 76.50
                ['medical_service_id' => $this->service2->id, 'quantity' => 2], // 15.00 * 2 = 30.00
            ]
        );

        $this->assertTrue($result['status']);

        $prescription = Prescription::with('items')->find($result['prescription_id']);
        $this->assertEquals('106.50', $prescription->total_amount);
    }

    // ─── Empty items rejected ─────────────────────────────────────────

    public function test_create_with_no_items_fails(): void
    {
        $result = $this->prescriptionService->createPrescription(
            ['patient_id' => $this->patient->id],
            []
        );

        $this->assertFalse($result['status']);
    }

    public function test_save_and_settle_with_no_items_fails(): void
    {
        $result = $this->prescriptionService->saveAndSettle(
            ['patient_id' => $this->patient->id],
            []
        );

        $this->assertFalse($result['status']);
    }

    /**
     * 按预约开处方时要把 patient_id 一并落库。
     *
     * createPrescriptions() 原先只写 appointment_id，而处方列表按
     * prescriptions.patient_id 关联患者表 —— 那批记录在列表上「患者编号」
     * 「患者姓名」两列全是空的，前台看不出这张处方是给谁开的。
     */
    public function test_prescriptions_created_for_an_appointment_carry_the_patient(): void
    {
        $appointment = \App\Appointment::create([
            'appointment_no' => \App\Appointment::AppointmentNo(),
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->admin->id,
            '_who_added'     => $this->admin->id,
        ]);

        $this->prescriptionService->createPrescriptions($appointment->id, [
            ['drug' => '阿莫西林胶囊', 'qty' => 12, 'directions' => '口服 每次1粒'],
        ]);

        $this->assertDatabaseHas('prescriptions', [
            'appointment_id' => $appointment->id,
            'patient_id'     => $this->patient->id,
            'drug'           => '阿莫西林胶囊',
        ]);
    }

    /**
     * 历史处方只写了 appointment_id，列表要能经预约回落取到患者。
     */
    public function test_legacy_prescription_without_patient_id_still_shows_the_patient(): void
    {
        $appointment = \App\Appointment::create([
            'appointment_no' => \App\Appointment::AppointmentNo(),
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->admin->id,
            '_who_added'     => $this->admin->id,
        ]);

        // 绕开服务层直接建，模拟升级前留下的那批 patient_id 为 NULL 的记录
        \App\Prescription::create([
            'drug'           => '历史药',
            'qty'            => 1,
            'directions'     => '历史用法',
            'appointment_id' => $appointment->id,
            '_who_added'     => $this->admin->id,
        ]);

        $row = $this->prescriptionService->getAllPrescriptions()
            ->firstWhere('drug', '历史药');

        $this->assertNotNull($row);
        $this->assertSame(
            $this->patient->patient_no,
            $row->patient_no,
            '历史处方应当经预约回落取到患者，否则列表上患者列是空的'
        );
    }
}
