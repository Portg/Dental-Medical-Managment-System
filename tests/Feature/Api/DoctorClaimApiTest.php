<?php

namespace Tests\Feature\Api;

use App\Appointment;
use App\Branch;
use App\ClaimRate;
use App\Patient;
use App\Role;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class DoctorClaimApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $doctor;
    private Patient $patient;
    private Appointment $appointment;
    private ClaimRate $claimRate;
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
            'visit_information' => 'appointment',
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->doctor->id,
            'branch_id'         => $branch->id,
            '_who_added'        => $this->admin->id,
            'sort_by'           => now()->addDay()->format('Y-m-d') . ' 10:00:00',
        ]);

        $this->claimRate = ClaimRate::create([
            'doctor_id'      => $this->doctor->id,
            'insurance_rate' => 10,
            'cash_rate'      => 15,
            'status'         => 'active',
            '_who_added'     => $this->admin->id,
        ]);

        $this->token = $this->admin->createToken('test')->plainTextToken;
    }

    private function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    private function validClaimData(array $overrides = []): array
    {
        return array_merge([
            'claim_amount'     => 1000,
            'insurance_amount' => 500,
            'cash_amount'      => 500,
            'claim_rate_id'    => $this->claimRate->id,
            'appointment_id'   => $this->appointment->id,
        ], $overrides);
    }

    // ─── List ───────────────────────────────────────────────────────

    public function test_list_doctor_claims(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/doctor-claims');

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    // ─── Create ─────────────────────────────────────────────────────

    public function test_create_doctor_claim(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/doctor-claims', $this->validClaimData());

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('doctor_claims', [
            'claim_amount'   => 1000,
            'appointment_id' => $this->appointment->id,
        ]);
    }

    public function test_create_doctor_claim_validation_fails(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/doctor-claims', []);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false);
    }

    // ─── Show ───────────────────────────────────────────────────────

    public function test_show_doctor_claim(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/doctor-claims', $this->validClaimData());

        $claimId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/v1/doctor-claims/{$claimId}");

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.id', $claimId);
    }

    // ─── Update ─────────────────────────────────────────────────────

    public function test_update_doctor_claim(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/doctor-claims', $this->validClaimData());

        $claimId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/v1/doctor-claims/{$claimId}", [
                'insurance_amount' => 600,
                'cash_amount'      => 400,
            ]);

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('doctor_claims', [
            'id'               => $claimId,
            'insurance_amount' => 600,
            'cash_amount'      => 400,
        ]);
    }

    // ─── Delete ─────────────────────────────────────────────────────

    public function test_delete_doctor_claim(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/doctor-claims', $this->validClaimData());

        $claimId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/v1/doctor-claims/{$claimId}");

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertSoftDeleted('doctor_claims', ['id' => $claimId]);
    }

    // ─── Approve ────────────────────────────────────────────────────

    public function test_approve_doctor_claim(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/doctor-claims', $this->validClaimData());

        $claimId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/v1/doctor-claims/{$claimId}/approve", [
                'insurance_amount' => 500,
                'cash_amount'      => 500,
            ]);

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('doctor_claims', [
            'id'     => $claimId,
            'status' => 'Approved',
        ]);
    }
}
