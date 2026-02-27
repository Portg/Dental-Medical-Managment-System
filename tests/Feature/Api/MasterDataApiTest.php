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

class MasterDataApiTest extends TestCase
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
            'visit_information'  => 'appointment',
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

    // ═══════════════════════════════════════════════════════════════
    // Medical Services
    // ═══════════════════════════════════════════════════════════════

    // ─── List ──────────────────────────────────────────────────────

    public function test_list_medical_services(): void
    {
        // Create a service first
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/medical-services', [
                'name'  => '牙齿美白',
                'price' => 500,
            ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/medical-services');

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure(['success', 'data', 'meta' => ['total']]);
    }

    // ─── Create ────────────────────────────────────────────────────

    public function test_create_medical_service(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/medical-services', [
                'name'  => '牙齿美白',
                'price' => 500,
            ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('medical_services', [
            'name'  => '牙齿美白',
            'price' => 500,
        ]);
    }

    public function test_create_medical_service_validation_fails(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/medical-services', []);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false);
    }

    // ─── Show ──────────────────────────────────────────────────────

    public function test_show_medical_service(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/medical-services', [
                'name'  => '牙齿美白',
                'price' => 500,
            ]);

        $serviceId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/v1/medical-services/{$serviceId}");

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.id', $serviceId);
    }

    // ─── Update ────────────────────────────────────────────────────

    public function test_update_medical_service(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/medical-services', [
                'name'  => '牙齿美白',
                'price' => 500,
            ]);

        $serviceId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/v1/medical-services/{$serviceId}", [
                'name'  => '超声波洁牙',
                'price' => 600,
            ]);

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('medical_services', [
            'id'    => $serviceId,
            'name'  => '超声波洁牙',
            'price' => 600,
        ]);
    }

    // ─── Delete ────────────────────────────────────────────────────

    public function test_delete_medical_service(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/medical-services', [
                'name'  => '牙齿美白',
                'price' => 500,
            ]);

        $serviceId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/v1/medical-services/{$serviceId}");

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    // ═══════════════════════════════════════════════════════════════
    // Suppliers
    // ═══════════════════════════════════════════════════════════════

    // ─── List ──────────────────────────────────────────────────────

    public function test_list_suppliers(): void
    {
        // Create a supplier first
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/suppliers', [
                'name' => '上海医疗器械有限公司',
            ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/suppliers');

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure(['success', 'data', 'meta' => ['total']]);
    }

    // ─── Create ────────────────────────────────────────────────────

    public function test_create_supplier(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/suppliers', [
                'name' => '上海医疗器械有限公司',
            ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('suppliers', [
            'name' => '上海医疗器械有限公司',
        ]);
    }

    public function test_create_supplier_validation_fails(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/suppliers', []);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false);
    }

    // ─── Show ──────────────────────────────────────────────────────

    public function test_show_supplier(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/suppliers', [
                'name' => '上海医疗器械有限公司',
            ]);

        $supplierId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/v1/suppliers/{$supplierId}");

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.id', $supplierId);
    }

    // ─── Update ────────────────────────────────────────────────────

    public function test_update_supplier(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/suppliers', [
                'name' => '上海医疗器械有限公司',
            ]);

        $supplierId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/v1/suppliers/{$supplierId}", [
                'name' => '北京医疗器械集团',
            ]);

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('suppliers', [
            'id'   => $supplierId,
            'name' => '北京医疗器械集团',
        ]);
    }

    // ─── Delete ────────────────────────────────────────────────────

    public function test_delete_supplier(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/suppliers', [
                'name' => '上海医疗器械有限公司',
            ]);

        $supplierId = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/v1/suppliers/{$supplierId}");

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }
}
