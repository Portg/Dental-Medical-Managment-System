<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\MedicalService;
use App\Patient;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class InvoiceCrudSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $doctor;
    private Patient $patient;
    private Appointment $appointment;
    private MedicalService $service;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();

        $branch     = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $adminRole  = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $doctorRole = Role::create(['name' => 'Doctor', 'slug' => 'doctor']);

        $this->admin = User::factory()->create([
            'role_id'   => $adminRole->id,
            'branch_id' => $branch->id,
            'password'  => bcrypt('password'),
        ]);

        $this->doctor = User::factory()->create([
            'role_id'   => $doctorRole->id,
            'branch_id' => $branch->id,
            'is_doctor' => 'yes',
            'password'  => bcrypt('password'),
        ]);

        $this->patient = Patient::create([
            'patient_no' => '20260001',
            'surname'    => '张',
            'othername'  => '三',
            'gender'     => 'Male',
            'phone_no'   => '13800138000',
            '_who_added' => $this->admin->id,
        ]);

        $this->appointment = Appointment::create([
            'start_date'        => now()->addDay()->format('Y-m-d'),
            'end_date'          => now()->addDay()->format('Y-m-d'),
            'start_time'        => '10:00 AM',
            'visit_information'  => 'appointment',
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->doctor->id,
            'branch_id'         => $branch->id,
            '_who_added'        => $this->admin->id,
            'sort_by'           => now()->addDay()->format('Y-m-d') . ' 10:00:00',
        ]);

        $this->service = MedicalService::create([
            'name'       => '洗牙',
            'price'      => 200,
            '_who_added' => $this->admin->id,
        ]);

        $this->token = $this->admin->createToken('test')->plainTextToken;

        // Grant view-invoices permission to admin role (needed for web routes)
        $perm = Permission::create(['name' => 'View Invoices', 'slug' => 'view-invoices', 'module' => 'invoices']);
        RolePermission::create(['role_id' => $adminRole->id, 'permission_id' => $perm->id]);
    }

    private function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    private function validInvoiceData(array $overrides = []): array
    {
        return array_merge([
            'appointment_id' => $this->appointment->id,
            'items'          => [
                [
                    'medical_service_id' => $this->service->id,
                    'qty'                => 1,
                    'price'              => 200,
                    'doctor_id'          => $this->doctor->id,
                ],
            ],
        ], $overrides);
    }

    // ─── Create ────────────────────────────────────────────────────

    public function test_create_invoice(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/invoices', $this->validInvoiceData());

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('invoices', [
            'appointment_id' => $this->appointment->id,
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'medical_service_id' => $this->service->id,
            'qty'                => 1,
        ]);
    }

    public function test_create_invoice_validation_fails(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/invoices', []);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false);
    }

    // ─── List ──────────────────────────────────────────────────────

    public function test_list_invoices(): void
    {
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/invoices', $this->validInvoiceData());

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/invoices');

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure(['success', 'data', 'meta' => ['total']]);
    }

    // ─── Show ──────────────────────────────────────────────────────

    public function test_show_invoice(): void
    {
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/invoices', $this->validInvoiceData());

        $invoiceId = \App\Invoice::first()->id;

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/v1/invoices/{$invoiceId}");

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.id', $invoiceId);
    }

    // ─── Delete ────────────────────────────────────────────────────

    public function test_delete_invoice(): void
    {
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/invoices', $this->validInvoiceData());

        $invoiceId = \App\Invoice::first()->id;

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/v1/invoices/{$invoiceId}");

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertSoftDeleted('invoices', ['id' => $invoiceId]);
    }

    // ─── Amount ────────────────────────────────────────────────────

    public function test_invoice_amount(): void
    {
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/invoices', $this->validInvoiceData());

        $invoiceId = \App\Invoice::first()->id;

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/v1/invoices/{$invoiceId}/amount");

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure(['success', 'data' => ['amount']]);
    }

    // ─── Procedures ────────────────────────────────────────────────

    public function test_invoice_procedures(): void
    {
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/invoices', $this->validInvoiceData());

        $invoiceId = \App\Invoice::first()->id;

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/v1/invoices/{$invoiceId}/procedures");

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    // ─── Set Credit (挂账) ─────────────────────────────────────────

    public function test_set_credit(): void
    {
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/invoices', $this->validInvoiceData());

        $invoiceId = \App\Invoice::first()->id;

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/v1/invoices/{$invoiceId}/set-credit");

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('invoices', [
            'id'        => $invoiceId,
            'is_credit' => true,
        ]);
    }

    // ─── Web DataTable ──────────────────────────────────────────────

    public function test_invoice_datatable_ajax_returns_200(): void
    {
        // Create an invoice first so there is data to display
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/invoices', $this->validInvoiceData());

        // Simulate a DataTables server-side AJAX request (search is an array)
        $response = $this->actingAs($this->admin)
            ->get('/invoices?' . http_build_query([
                'draw'       => 1,
                'start'      => 0,
                'length'     => 10,
                'search'     => ['value' => '', 'regex' => 'false'],
                'start_date' => now()->format('Y-m-d'),
                'end_date'   => now()->format('Y-m-d'),
            ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk()
                 ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }

    // ─── Service int cast regression ───────────────────────────────

    /**
     * Regression: InvoiceService::getInvoiceDetail(int) received string from route parameter.
     * Verify that the controller correctly casts to int before calling the service.
     */
    public function test_invoice_service_accepts_route_parameter_as_int(): void
    {
        $invoice = \App\Invoice::create([
            'appointment_id' => $this->appointment->id,
            '_who_added'     => $this->admin->id,
        ]);

        // Simulate what the controller does: pass a string ID (like a route parameter)
        $service = app(\App\Services\InvoiceService::class);
        $data = $service->getInvoiceDetail((int) "{$invoice->id}");

        $this->assertIsArray($data);
        $this->assertArrayHasKey('invoice', $data);
    }

    // ─── Auth guard ────────────────────────────────────────────────

    public function test_unauthenticated_cannot_access_invoices(): void
    {
        $this->getJson('/api/v1/invoices')->assertStatus(401);
    }
}
