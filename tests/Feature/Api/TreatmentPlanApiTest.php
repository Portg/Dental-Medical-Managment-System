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

class TreatmentPlanApiTest extends TestCase
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

    public function test_list_treatment_plans(): void
    {
        // Create a plan first
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/treatment-plans', [
                'plan_name'  => '根管治疗方案',
                'patient_id' => $this->patient->id,
            ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/treatment-plans');

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure(['success', 'data', 'meta' => ['current_page', 'total']]);
    }

    // ─── Create ────────────────────────────────────────────────────

    public function test_create_treatment_plan(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/treatment-plans', [
                'plan_name'  => '根管治疗方案',
                'patient_id' => $this->patient->id,
            ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('treatment_plans', [
            'plan_name'  => '根管治疗方案',
            'patient_id' => $this->patient->id,
        ]);
    }

    public function test_create_treatment_plan_validation_fails(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/treatment-plans', []);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false);
    }

    // ─── Show ──────────────────────────────────────────────────────

    public function test_show_treatment_plan(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/treatment-plans', [
                'plan_name'  => '根管治疗方案',
                'patient_id' => $this->patient->id,
            ]);

        $planId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/v1/treatment-plans/{$planId}");

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.id', $planId)
                 ->assertJsonStructure(['success', 'data' => ['id', 'plan_name', 'items', 'stages']]);
    }

    // ─── Update ────────────────────────────────────────────────────

    public function test_update_treatment_plan(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/treatment-plans', [
                'plan_name'  => '根管治疗方案',
                'patient_id' => $this->patient->id,
            ]);

        $planId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/v1/treatment-plans/{$planId}", [
                'plan_name'   => '修改后的方案',
                'description' => '更新描述',
                'status'      => 'in_progress',
            ]);

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('treatment_plans', [
            'id'        => $planId,
            'plan_name' => '修改后的方案',
        ]);
    }

    // ─── Delete (soft) ─────────────────────────────────────────────

    public function test_delete_treatment_plan(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/treatment-plans', [
                'plan_name'  => '根管治疗方案',
                'patient_id' => $this->patient->id,
            ]);

        $planId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/v1/treatment-plans/{$planId}");

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertSoftDeleted('treatment_plans', ['id' => $planId]);
    }

    // ─── Patient plans ─────────────────────────────────────────────

    public function test_patient_plans(): void
    {
        // Create a plan for the patient
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/treatment-plans', [
                'plan_name'  => '根管治疗方案',
                'patient_id' => $this->patient->id,
            ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/treatment-plans/patient/' . $this->patient->id);

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }
}
