<?php

namespace Tests\Feature\Api;

use App\Appointment;
use App\Branch;
use App\MedicalService;
use App\Patient;
use App\Role;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class QuotationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $doctor;
    private Patient $patient;
    private Appointment $appointment;
    private MedicalService $medicalService;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();

        $branch     = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $adminRole  = Role::create(['name' => 'Super Administrator', 'slug' => 'super-admin']);
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

        $this->medicalService = MedicalService::create([
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

    // ─── List ──────────────────────────────────────────────────────

    public function test_list_quotations(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/quotations');

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    // ─── Create ────────────────────────────────────────────────────

    public function test_create_quotation(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/quotations', [
                'patient_id' => $this->patient->id,
                'items'      => [
                    [
                        'qty'                => 1,
                        'amount'             => 200,
                        'medical_service_id' => $this->medicalService->id,
                    ],
                ],
            ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('quotations', [
            'patient_id' => $this->patient->id,
        ]);

        $this->assertDatabaseHas('quotation_items', [
            'medical_service_id' => $this->medicalService->id,
            'qty'                => 1,
            'amount'             => 200,
        ]);
    }

    public function test_create_quotation_validation_fails(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/quotations', []);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false);
    }

    // ─── Show ──────────────────────────────────────────────────────

    public function test_show_quotation(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/quotations', [
                'patient_id' => $this->patient->id,
                'items'      => [
                    [
                        'qty'                => 1,
                        'amount'             => 200,
                        'medical_service_id' => $this->medicalService->id,
                    ],
                ],
            ]);

        $quotationId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/v1/quotations/{$quotationId}");

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.id', $quotationId)
                 ->assertJsonStructure(['success', 'data' => ['id', 'items']]);
    }

    // ─── Delete ────────────────────────────────────────────────────

    public function test_delete_quotation(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/quotations', [
                'patient_id' => $this->patient->id,
                'items'      => [
                    [
                        'qty'                => 1,
                        'amount'             => 200,
                        'medical_service_id' => $this->medicalService->id,
                    ],
                ],
            ]);

        $quotationId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/v1/quotations/{$quotationId}");

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }
}
