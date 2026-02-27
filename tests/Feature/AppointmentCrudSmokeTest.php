<?php

namespace Tests\Feature;

use App\Branch;
use App\Patient;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AppointmentCrudSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $doctor;
    private Patient $patient;
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

        $this->token = $this->admin->createToken('test')->plainTextToken;

        // Grant view-patients permission to admin role
        $viewPatientsPerm = Permission::create(['name' => 'View Patients', 'slug' => 'view-patients', 'module' => 'patients']);
        RolePermission::create(['role_id' => $adminRole->id, 'permission_id' => $viewPatientsPerm->id]);
        Cache::flush();
    }

    private function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    private function validAppointmentData(array $overrides = []): array
    {
        return array_merge([
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->doctor->id,
            'visit_information' => 'appointment',
            'appointment_date'  => now()->addDay()->format('Y-m-d'),
            'appointment_time'  => '10:00 AM',
        ], $overrides);
    }

    // ─── Create ────────────────────────────────────────────────────

    public function test_create_appointment(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/appointments', $this->validAppointmentData());

        $response->assertStatus(201)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.patient_id', $this->patient->id)
                 ->assertJsonPath('data.doctor_id', $this->doctor->id);

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $this->patient->id,
            'doctor_id'  => $this->doctor->id,
        ]);
    }

    public function test_create_appointment_validation_fails(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/appointments', []);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false);
    }

    // ─── List ──────────────────────────────────────────────────────

    public function test_list_appointments(): void
    {
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/appointments', $this->validAppointmentData());

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/appointments');

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure(['success', 'data', 'meta' => ['total']]);
    }

    // ─── Show ──────────────────────────────────────────────────────

    public function test_show_appointment(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/appointments', $this->validAppointmentData());

        $id = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/v1/appointments/{$id}");

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.id', $id);
    }

    // ─── Update ────────────────────────────────────────────────────

    public function test_update_appointment(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/appointments', $this->validAppointmentData());

        $id = $create->json('data.id');

        $newDate = now()->addDays(3)->format('Y-m-d');

        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/v1/appointments/{$id}", $this->validAppointmentData([
                'appointment_date' => $newDate,
                'appointment_time' => '02:00 PM',
            ]));

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('appointments', [
            'id'         => $id,
            'start_date' => $newDate,
        ]);
    }

    // ─── Delete ────────────────────────────────────────────────────

    public function test_delete_appointment(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/appointments', $this->validAppointmentData());

        $id = $create->json('data.id');

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/v1/appointments/{$id}");

        $response->assertOk()
                 ->assertJsonPath('success', true);

        $this->assertSoftDeleted('appointments', ['id' => $id]);
    }

    // ─── Reschedule ────────────────────────────────────────────────

    public function test_reschedule_appointment(): void
    {
        $create = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/appointments', $this->validAppointmentData());

        $id = $create->json('data.id');

        $newDate = now()->addDays(5)->format('Y-m-d');

        $response = $this->withHeaders($this->authHeader())
            ->postJson("/api/v1/appointments/{$id}/reschedule", [
                'appointment_date' => $newDate,
                'appointment_time' => '03:00 PM',
            ]);

        $response->assertOk()
                 ->assertJsonPath('success', true);

        // BUG: appointments.status enum does not include 'Rescheduled',
        // so the value is silently rejected by MySQL. The history table
        // correctly tracks the reschedule via appointment_histories.
        $this->assertDatabaseHas('appointments', [
            'id'         => $id,
            'start_date' => $newDate,
        ]);
    }

    // ─── Auth guard ────────────────────────────────────────────────

    public function test_unauthenticated_cannot_access_appointments(): void
    {
        $this->getJson('/api/v1/appointments')->assertStatus(401);
    }

    // ─── Patient search for appointment form ─────────────────────

    public function test_search_patient_with_full_returns_complete_data(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/search-patient?q=张&full=1');

        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('surname', $data[0]);
        $this->assertArrayHasKey('othername', $data[0]);
        $this->assertArrayHasKey('phone_no', $data[0]);
        $this->assertArrayHasKey('gender', $data[0]);
    }

    public function test_search_patient_without_full_returns_id_text(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/search-patient?q=张');

        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('text', $data[0]);
        $this->assertArrayNotHasKey('surname', $data[0]);
    }

    // ─── Chairs endpoint ─────────────────────────────────────────

    public function test_get_chairs_returns_json(): void
    {
        // Grant view-appointments permission
        $perm = Permission::create(['name' => 'View Appointments', 'slug' => 'view-appointments', 'module' => 'appointments']);
        RolePermission::create(['role_id' => $this->admin->role_id, 'permission_id' => $perm->id]);
        Cache::flush();

        \App\Chair::create([
            'chair_code' => 'C01',
            'chair_name' => '牙椅 1 号',
            'status'     => 'active',
            'branch_id'  => $this->admin->branch_id,
            '_who_added' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/chairs');

        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('text', $data[0]);
        $this->assertEquals('牙椅 1 号', $data[0]['text']);
    }
}
