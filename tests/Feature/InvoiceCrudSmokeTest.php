<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\MedicalService;
use App\Patient;
use App\Role;
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

        $branch     = Branch::create(['name' => 'Main Branch', 'is_active' => 'true']);
        $adminRole  = Role::create(['name' => 'Administrator']);
        $doctorRole = Role::create(['name' => 'Doctor']);

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

    // ─── Auth guard ────────────────────────────────────────────────

    public function test_unauthenticated_cannot_access_invoices(): void
    {
        $this->getJson('/api/v1/invoices')->assertStatus(401);
    }
}
