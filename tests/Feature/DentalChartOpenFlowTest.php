<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\Patient;
use App\Role;
use App\Services\DentalChartService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 牙科图表：按患者直接打开编辑页（不绕预约中心）。
 */
class DentalChartOpenFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Test Branch', 'is_active' => true]);
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);

        $this->admin = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $branch->id,
            'status'    => User::STATUS_ACTIVE,
            'is_doctor' => true,
        ]);

        $this->patient = Patient::create([
            'patient_no' => 'DC20260801',
            'surname'    => '张',
            'othername'  => '三',
            'gender'     => 'Male',
            'phone_no'   => '13900001111',
            '_who_added' => $this->admin->id,
        ]);
    }

    public function test_open_for_patient_reuses_existing_appointment(): void
    {
        $appointment = Appointment::create([
            'appointment_no'    => 900001,
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->admin->id,
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->toDateString(),
            'start_time'        => '09:00:00',
            'status'            => Appointment::STATUS_SCHEDULED,
            'visit_information' => Appointment::VISIT_WALK_IN,
            'branch_id'         => $this->admin->branch_id,
            '_who_added'        => $this->admin->id,
        ]);

        $before = Appointment::where('patient_id', $this->patient->id)->count();

        $this->actingAs($this->admin)
            ->get('/dental-charting/for-patient/' . $this->patient->id)
            ->assertRedirect('/dental-charting/open/' . $appointment->id);

        $this->assertSame($before, Appointment::where('patient_id', $this->patient->id)->count());

        $this->actingAs($this->admin)
            ->get('/dental-charting/open/' . $appointment->id)
            ->assertOk()
            ->assertSee('global_appointment_id', false)
            ->assertSee((string) $appointment->id);
    }

    public function test_open_for_patient_creates_appointment_only_when_none_exist(): void
    {
        $this->assertSame(0, Appointment::where('patient_id', $this->patient->id)->count());

        $response = $this->actingAs($this->admin)
            ->get('/dental-charting/for-patient/' . $this->patient->id);

        $appointment = Appointment::where('patient_id', $this->patient->id)->first();
        $this->assertNotNull($appointment);
        $response->assertRedirect('/dental-charting/open/' . $appointment->id);
    }

    public function test_resolve_reuses_cancelled_appointment_instead_of_creating(): void
    {
        $cancelled = Appointment::create([
            'appointment_no'    => 900002,
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->admin->id,
            'start_date'        => now()->subDay()->toDateString(),
            'end_date'          => now()->subDay()->toDateString(),
            'start_time'        => '10:00:00',
            'status'            => Appointment::STATUS_CANCELLED,
            'visit_information' => Appointment::VISIT_WALK_IN,
            'branch_id'         => $this->admin->branch_id,
            '_who_added'        => $this->admin->id,
        ]);

        $resolved = app(DentalChartService::class)->resolveAppointmentForChart($this->patient->id);

        $this->assertSame($cancelled->id, $resolved->id);
        $this->assertSame(1, Appointment::where('patient_id', $this->patient->id)->count());
    }

    public function test_index_requires_auth_permission(): void
    {
        $this->get('/dental-charting')->assertRedirect();

        $noPerm = User::factory()->create([
            'role_id'   => Role::create(['name' => 'Guest', 'slug' => 'guest-role'])->id,
            'branch_id' => $this->admin->branch_id,
            'status'    => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($noPerm)->get('/dental-charting')->assertForbidden();
    }
}
