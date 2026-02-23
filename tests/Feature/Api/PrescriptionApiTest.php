<?php

namespace Tests\Feature\Api;

use App\Appointment;
use App\Branch;
use App\Patient;
use App\Role;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class PrescriptionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $doctor;
    private Patient $patient;
    private Appointment $appointment;
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

        $this->token = $this->admin->createToken('test')->plainTextToken;
    }

    private function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    // ─── List ──────────────────────────────────────────────────────

    public function test_list_prescriptions(): void
    {
        // Create a prescription first
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/prescriptions', [
                'drug'           => '阿莫西林',
                'qty'            => '2盒',
                'directions'     => '每日3次',
                'appointment_id' => $this->appointment->id,
            ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/prescriptions');

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure(['success', 'data', 'meta' => ['current_page', 'total']]);
    }

    // ─── Create ────────────────────────────────────────────────────

    public function test_create_prescription(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/prescriptions', [
                'drug'           => '阿莫西林',
                'qty'            => '2盒',
                'directions'     => '每日3次',
                'appointment_id' => $this->appointment->id,
            ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('prescriptions', [
            'drug'           => '阿莫西林',
            'qty'            => '2盒',
            'directions'     => '每日3次',
            'appointment_id' => $this->appointment->id,
        ]);
    }

    public function test_create_prescription_validation_fails(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/prescriptions', []);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false);
    }

    // ─── Show ──────────────────────────────────────────────────────

    public function test_show_prescription(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/prescriptions', [
                'drug'           => '阿莫西林',
                'qty'            => '2盒',
                'directions'     => '每日3次',
                'appointment_id' => $this->appointment->id,
            ]);

        $prescriptionId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/v1/prescriptions/{$prescriptionId}");

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.id', $prescriptionId);
    }

    // ─── Update ────────────────────────────────────────────────────

    public function test_update_prescription(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/prescriptions', [
                'drug'           => '阿莫西林',
                'qty'            => '2盒',
                'directions'     => '每日3次',
                'appointment_id' => $this->appointment->id,
            ]);

        $prescriptionId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/v1/prescriptions/{$prescriptionId}", [
                'drug'       => '头孢克洛',
                'qty'        => '1盒',
                'directions' => '每日2次，饭后服用',
            ]);

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('prescriptions', [
            'id'         => $prescriptionId,
            'drug'       => '头孢克洛',
            'qty'        => '1盒',
            'directions' => '每日2次，饭后服用',
        ]);
    }

    // ─── Delete (soft) ─────────────────────────────────────────────

    public function test_delete_prescription(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/prescriptions', [
                'drug'           => '阿莫西林',
                'qty'            => '2盒',
                'directions'     => '每日3次',
                'appointment_id' => $this->appointment->id,
            ]);

        $prescriptionId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/v1/prescriptions/{$prescriptionId}");

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertSoftDeleted('prescriptions', ['id' => $prescriptionId]);
    }

    // ─── By appointment ────────────────────────────────────────────

    public function test_by_appointment(): void
    {
        // Create a prescription for the appointment
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/prescriptions', [
                'drug'           => '阿莫西林',
                'qty'            => '2盒',
                'directions'     => '每日3次',
                'appointment_id' => $this->appointment->id,
            ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/prescriptions/appointment/' . $this->appointment->id);

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    // ─── Drug names ────────────────────────────────────────────────

    public function test_drug_names(): void
    {
        // Create a prescription so there is at least one drug name
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/prescriptions', [
                'drug'           => '阿莫西林',
                'qty'            => '2盒',
                'directions'     => '每日3次',
                'appointment_id' => $this->appointment->id,
            ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/prescriptions/drug-names');

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure(['success', 'data']);

        $this->assertIsArray($response->json('data'));
    }
}
