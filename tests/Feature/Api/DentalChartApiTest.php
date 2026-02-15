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

class DentalChartApiTest extends TestCase
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

    private function validChartData(): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'chart_data'     => [
                [
                    'tooth'   => '11',
                    'section' => 'upper-right',
                    'color'   => 'red',
                ],
            ],
        ];
    }

    // ─── List ──────────────────────────────────────────────────────

    public function test_list_dental_charts(): void
    {
        // Store chart data first so there is something to list
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/dental-charts', $this->validChartData());

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/dental-charts');

        $response->assertOk()
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure(['success', 'data', 'meta' => ['current_page', 'total']]);
    }

    // ─── Store ─────────────────────────────────────────────────────

    public function test_store_dental_chart(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/dental-charts', $this->validChartData());

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('dental_charts', [
            'tooth'          => '11',
            'section'        => 'upper-right',
            'color'          => 'red',
            'appointment_id' => $this->appointment->id,
        ]);
    }

    public function test_store_validation_fails(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/dental-charts', []);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false);
    }

    // ─── Patient chart ─────────────────────────────────────────────

    public function test_patient_chart(): void
    {
        // Store chart data first
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/dental-charts', $this->validChartData());

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/dental-charts/patient/' . $this->patient->id);

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }

    // ─── Appointment chart ─────────────────────────────────────────

    public function test_appointment_chart(): void
    {
        // Store chart data first
        $this->withHeaders($this->authHeader())
            ->postJson('/api/v1/dental-charts', $this->validChartData());

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/v1/dental-charts/appointment/' . $this->appointment->id);

        $response->assertOk()
                 ->assertJsonPath('success', true);
    }
}
