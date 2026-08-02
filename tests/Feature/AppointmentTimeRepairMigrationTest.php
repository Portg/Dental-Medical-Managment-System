<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\Patient;
use App\Role;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 2026_08_02_135347 按 sort_by 还原被截断 AM/PM 的 start_time。
 *
 * 判据「sort_by 小时数 = start_time 小时数 + 12」只在 start_time 与 sort_by
 * 描述同一时刻时成立——对非 walk-in 成立（两者同源于 appointment_time），
 * 对 walk-in 不成立（start_time 是到店时间、sort_by 是预约时段，本就独立）。
 */
class AppointmentTimeRepairMigrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Patient $patient;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role         = Role::create(['name' => 'Super Administrator', 'slug' => 'super-admin']);

        $this->admin = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->patient = Patient::create([
            'patient_no' => '20260001',
            'surname'    => '张',
            'othername'  => '三',
            'gender'     => 'Male',
            '_who_added' => $this->admin->id,
        ]);
    }

    private function insertAppointment(string $startTime, string $sortByTime, ?string $visit): int
    {
        $date = '2026-03-11';

        return DB::table('appointments')->insertGetId([
            'start_date'        => $date,
            'end_date'          => $date,
            'start_time'        => $startTime,
            'sort_by'           => $date . ' ' . $sortByTime,
            'visit_information' => $visit,
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->admin->id,
            'branch_id'         => $this->branch->id,
            '_who_added'        => $this->admin->id,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }

    private function runMigration(): void
    {
        $migration = require database_path(
            'migrations/2026_08_02_135347_repair_appointment_start_time_pm_truncation.php'
        );
        $migration->up();
    }

    private function startTimeOf(int $id): string
    {
        return DB::table('appointments')->where('id', $id)->value('start_time');
    }

    public function test_truncated_pm_time_is_restored_for_regular_appointment(): void
    {
        // '08:30 PM' 被 LEFT(...,5) 截成 '08:30'，真值在 sort_by 里
        $id = $this->insertAppointment('08:30', '20:30:00', Appointment::VISIT_APPOINTMENT);

        $this->runMigration();

        $this->assertSame('20:30', $this->startTimeOf($id));
    }

    public function test_correct_am_time_is_left_alone(): void
    {
        // '08:30 AM' 截断后本就等于 24 小时制真值，两者一致，不该被改
        $id = $this->insertAppointment('08:30', '08:30:00', Appointment::VISIT_APPOINTMENT);

        $this->runMigration();

        $this->assertSame('08:30', $this->startTimeOf($id));
    }

    public function test_walk_in_is_never_touched_even_when_it_matches_the_heuristic(): void
    {
        // 合法的现场挂号：20:30 到店、08:30 时段，恰好命中「相差 12 小时」的判据。
        // walk-in 的两个字段本就是不同的量，按判据改会把到店时间冲成时段。
        $id = $this->insertAppointment('20:30', '08:30:00', Appointment::VISIT_WALK_IN);

        $this->runMigration();

        $this->assertSame('20:30', $this->startTimeOf($id), 'walk-in 的到店时间不应被预约时段覆盖');
    }

    public function test_null_visit_information_is_still_repaired(): void
    {
        // SQL 里 NULL <> 'walk_in' 结果是 NULL 而非 true——
        // 排除条件写错的话这类行会被静默漏掉，修不到
        $id = $this->insertAppointment('09:15', '21:15:00', null);

        $this->runMigration();

        $this->assertSame('21:15', $this->startTimeOf($id));
    }
}
