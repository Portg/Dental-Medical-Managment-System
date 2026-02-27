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

class TreatmentApiTest extends TestCase
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

    public function test_list_treatments(): void
    {
        // Create a treatment first
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/treatments', [
                'clinical_notes' => '检查正常',
                'treatment'      => '洗牙',
                'appointment_id' => $this->appointment->id,
            ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/treatments');

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure(['success', 'data', 'meta' => ['current_page', 'total']]);
    }

    // ─── Create ────────────────────────────────────────────────────

    public function test_create_treatment(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/treatments', [
                'clinical_notes' => '检查正常',
                'treatment'      => '洗牙',
                'appointment_id' => $this->appointment->id,
            ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('treatments', [
            'clinical_notes' => '检查正常',
            'treatment'      => '洗牙',
            'appointment_id' => $this->appointment->id,
        ]);
    }

    public function test_create_treatment_validation_fails(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/treatments', []);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false);
    }

    // ─── Show ──────────────────────────────────────────────────────

    public function test_show_treatment(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/treatments', [
                'clinical_notes' => '检查正常',
                'treatment'      => '洗牙',
                'appointment_id' => $this->appointment->id,
            ]);

        $treatmentId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/v1/treatments/{$treatmentId}");

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.id', $treatmentId);
    }

    // ─── Update ────────────────────────────────────────────────────

    public function test_update_treatment(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/treatments', [
                'clinical_notes' => '检查正常',
                'treatment'      => '洗牙',
                'appointment_id' => $this->appointment->id,
            ]);

        $treatmentId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/v1/treatments/{$treatmentId}", [
                'clinical_notes' => '牙龈有轻微出血',
                'treatment'      => '深度洁牙',
                'appointment_id' => $this->appointment->id,
            ]);

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('treatments', [
            'id'             => $treatmentId,
            'clinical_notes' => '牙龈有轻微出血',
            'treatment'      => '深度洁牙',
        ]);
    }

    // ─── Delete (soft) ─────────────────────────────────────────────

    public function test_delete_treatment(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/treatments', [
                'clinical_notes' => '检查正常',
                'treatment'      => '洗牙',
                'appointment_id' => $this->appointment->id,
            ]);

        $treatmentId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/v1/treatments/{$treatmentId}");

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertSoftDeleted('treatments', ['id' => $treatmentId]);
    }

    // ─── Filter by appointment_id ──────────────────────────────────

    public function test_filter_by_appointment_id(): void
    {
        // Create a treatment for the known appointment
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/treatments', [
                'clinical_notes' => '检查正常',
                'treatment'      => '洗牙',
                'appointment_id' => $this->appointment->id,
            ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/treatments?appointment_id=' . $this->appointment->id);

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('meta.total', 1);
    }
}
