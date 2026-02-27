<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\DoctorSchedule;
use App\Patient;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorTimeSlotTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $doctor;
    private string $token;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $adminRole    = Role::create(['name' => 'Super Administrator', 'slug' => 'super-admin']);
        $doctorRole   = Role::create(['name' => 'Doctor', 'slug' => 'doctor']);

        $this->admin = User::factory()->create([
            'role_id'   => $adminRole->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->doctor = User::factory()->create([
            'role_id'   => $doctorRole->id,
            'branch_id' => $this->branch->id,
            'is_doctor' => 'yes',
        ]);

        $this->token = $this->admin->createToken('test')->plainTextToken;

        $perm = Permission::create(['name' => 'View Appointments', 'slug' => 'view-appointments', 'module' => 'appointments']);
        RolePermission::create(['role_id' => $adminRole->id, 'permission_id' => $perm->id]);
    }

    private function getSlots(string $date): \Illuminate\Testing\TestResponse
    {
        return $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/appointments/doctor-time-slots?doctor_id={$this->doctor->id}&date={$date}");
    }

    // ─── Default slots when no schedule ─────────────────────────

    public function test_default_slots_when_no_schedule(): void
    {
        $response = $this->getSlots('2026-03-10');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $slots = $response->json('data.slots');
        // 08:30-18:00 in 30-min intervals = 20 slots, no rest
        $this->assertCount(20, $slots);

        // First slot is 08:30 morning
        $this->assertEquals('08:30', $slots[0]['time']);
        $this->assertEquals('morning', $slots[0]['period']);
        $this->assertFalse($slots[0]['is_rest']);

        // Last slot is 18:00 afternoon
        $this->assertEquals('18:00', $slots[19]['time']);
        $this->assertEquals('afternoon', $slots[19]['period']);
        $this->assertFalse($slots[19]['is_rest']);
    }

    // ─── Custom schedule generates correct slots ────────────────

    public function test_custom_schedule_generates_slots(): void
    {
        DoctorSchedule::create([
            'doctor_id'     => $this->doctor->id,
            'schedule_date' => '2026-03-11',
            'start_time'    => '08:00',
            'end_time'      => '17:00',
            'branch_id'     => $this->branch->id,
            '_who_added'    => $this->admin->id,
        ]);

        $response = $this->getSlots('2026-03-11');

        $response->assertOk();

        $slots = $response->json('data.slots');

        // 08:00-17:00 in 30-min intervals = 18 slots
        $this->assertCount(18, $slots);

        // First slot
        $this->assertEquals('08:00', $slots[0]['time']);
        $this->assertEquals('morning', $slots[0]['period']);
        $this->assertFalse($slots[0]['is_rest']);

        // All slots should be available (no hardcoded lunch rest)
        foreach ($slots as $slot) {
            $this->assertFalse($slot['is_rest'], "Slot {$slot['time']} should not be rest");
        }

        // 12:00 and 14:00 should both be present and available
        $slot1200 = collect($slots)->firstWhere('time', '12:00');
        $this->assertNotNull($slot1200);
        $this->assertFalse($slot1200['is_rest']);

        $slot1400 = collect($slots)->firstWhere('time', '14:00');
        $this->assertNotNull($slot1400);
        $this->assertFalse($slot1400['is_rest']);
    }

    // ─── Booked slots are reported ──────────────────────────────

    public function test_booked_slots_marked(): void
    {
        $patient = Patient::create([
            'patient_no' => '20260099',
            'surname'    => '李',
            'othername'  => '四',
            'gender'     => 'Male',
            'phone_no'   => '13900139000',
            '_who_added' => $this->admin->id,
        ]);

        Appointment::create([
            'patient_id'  => $patient->id,
            'doctor_id'   => $this->doctor->id,
            'start_date'  => '2026-03-12',
            'start_time'  => '10:00:00',
            'status'      => 'Pending',
            '_who_added'  => $this->admin->id,
        ]);

        $response = $this->getSlots('2026-03-12');

        $response->assertOk();

        $booked = $response->json('data.booked');
        $this->assertArrayHasKey('10:00', $booked);
        $this->assertStringContains('李', $booked['10:00']['patient_name']);
    }

    // ─── Soft-deleted appointment does NOT block slot ─────────────

    public function test_deleted_appointment_not_blocking(): void
    {
        $patient = Patient::create([
            'patient_no' => '20260100',
            'surname'    => '王',
            'othername'  => '五',
            'gender'     => 'Female',
            'phone_no'   => '13700137000',
            '_who_added' => $this->admin->id,
        ]);

        $appt = Appointment::create([
            'patient_id'  => $patient->id,
            'doctor_id'   => $this->doctor->id,
            'start_date'  => '2026-03-13',
            'start_time'  => '14:30:00',
            'status'      => 'Waiting',
            '_who_added'  => $this->admin->id,
        ]);

        // Soft-delete the appointment
        $appt->delete();

        $response = $this->getSlots('2026-03-13');

        $response->assertOk();

        $booked = $response->json('data.booked');
        $this->assertArrayNotHasKey('14:30', $booked);
    }

    // ─── Edit endpoint returns Y-m-d date format ─────────────

    public function test_schedule_edit_returns_formatted_dates(): void
    {
        $schedulePerm = Permission::create(['name' => 'Manage Schedules', 'slug' => 'manage-schedules', 'module' => 'schedules']);
        RolePermission::create(['role_id' => $this->admin->role_id, 'permission_id' => $schedulePerm->id]);

        $schedule = DoctorSchedule::create([
            'doctor_id'        => $this->doctor->id,
            'schedule_date'    => '2026-04-15',
            'start_time'       => '09:00',
            'end_time'         => '17:00',
            'max_patients'     => 5,
            'is_recurring'     => true,
            'recurring_pattern'=> 'weekly',
            'recurring_until'  => '2026-06-30',
            'branch_id'        => $this->branch->id,
            '_who_added'       => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/doctor-schedules/{$schedule->id}/edit");

        $response->assertOk();

        $data = $response->json();

        // Dates must be Y-m-d, not ISO 8601, so datepicker can display them
        $this->assertEquals('2026-04-15', $data['schedule_date']);
        $this->assertEquals('2026-06-30', $data['recurring_until']);
    }

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'"
        );
    }
}
