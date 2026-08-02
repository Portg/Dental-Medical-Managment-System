<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\Patient;
use App\Role;
use App\Services\DentalChartService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * dental_charts.tooth_status 的写入与读取。
 *
 * 这一列是 NOT NULL enum（2026_01_17_800003 建列，默认 normal），三条约束：
 *   1. 不能写 null——旧代码显式写 null 直接 SQLSTATE[23000]；
 *   2. 不能写枚举外的值——MySQL 严格模式 1265，非严格模式静默截断成空串；
 *   3. normal 是"没问题"，不该出现在患者牙位图摘要的标记里。
 */
class DentalChartToothStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Patient $patient;
    private Appointment $appointment;
    private DentalChartService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();

        $branch    = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $adminRole = Role::create(['name' => 'Super Administrator', 'slug' => 'super-admin']);

        $this->admin = User::factory()->create([
            'role_id'   => $adminRole->id,
            'branch_id' => $branch->id,
        ]);

        $this->patient = Patient::create([
            'patient_no' => '20260001',
            'surname'    => '张',
            'othername'  => '三',
            'gender'     => 'Male',
            '_who_added' => $this->admin->id,
        ]);

        $this->appointment = Appointment::create([
            'start_date'        => now()->addDay()->format('Y-m-d'),
            'end_date'          => now()->addDay()->format('Y-m-d'),
            'start_time'        => '10:00 AM',
            'visit_information' => 'appointment',
            'patient_id'        => $this->patient->id,
            'doctor_id'         => $this->admin->id,
            'branch_id'         => $branch->id,
            '_who_added'        => $this->admin->id,
            'sort_by'           => now()->addDay()->format('Y-m-d') . ' 10:00:00',
        ]);

        $this->actingAs($this->admin);

        $this->service = app(DentalChartService::class);
    }

    // ─── 写入 ──────────────────────────────────────────────────────

    public function test_status_is_derived_from_color_when_not_given(): void
    {
        // 旧版 Angular 牙位图只提交 color 编号，没有 tooth_status
        $this->service->replaceChartData($this->appointment->id, [
            ['tooth' => 11, 'color' => '2'],
        ]);

        $this->assertDatabaseHas('dental_charts', [
            'tooth'        => 11,
            'tooth_status' => 'caries',
        ]);
    }

    public function test_status_falls_back_to_normal_when_color_unmappable(): void
    {
        $this->service->replaceChartData($this->appointment->id, [
            ['tooth' => 12, 'color' => '99'],
            ['tooth' => 13],
        ]);

        // 关键是不能写 null：该列 NOT NULL，旧代码写 null 会 SQLSTATE[23000]
        $this->assertDatabaseHas('dental_charts', ['tooth' => 12, 'tooth_status' => 'normal']);
        $this->assertDatabaseHas('dental_charts', ['tooth' => 13, 'tooth_status' => 'normal']);
    }

    public function test_illegal_status_is_not_written_to_the_enum_column(): void
    {
        // Web 端 DentalChartController::store() 把请求体直接透传进来，没有 Validator
        // 把关，兜底必须在 Service 里
        $this->service->replaceChartData($this->appointment->id, [
            ['tooth' => 14, 'tooth_status' => 'DROP TABLE', 'color' => '4'],
            ['tooth' => 15, 'tooth_status' => 'not_an_enum_value'],
        ]);

        // 非法状态被丢弃，退回 color 折算
        $this->assertDatabaseHas('dental_charts', ['tooth' => 14, 'tooth_status' => 'missing']);
        // 连 color 都没有就退回 normal
        $this->assertDatabaseHas('dental_charts', ['tooth' => 15, 'tooth_status' => 'normal']);
    }

    public function test_explicit_status_wins_over_color(): void
    {
        $this->service->replaceChartData($this->appointment->id, [
            ['tooth' => 16, 'tooth_status' => 'implant', 'color' => '2'],
        ]);

        $this->assertDatabaseHas('dental_charts', [
            'tooth'        => 16,
            'tooth_status' => 'implant',
        ]);
    }

    // ─── 读取 ──────────────────────────────────────────────────────

    public function test_summary_excludes_normal_teeth(): void
    {
        $this->service->replaceChartData($this->appointment->id, [
            ['tooth' => 21, 'tooth_status' => 'caries', 'color' => '2'],
            ['tooth' => 22],                                            // → normal
        ]);

        $summary = $this->service->getChartSummaryForPatient($this->patient->id);
        $teeth   = array_column($summary['marks'], 'status', 'tooth');

        // normal 是"没问题"，冒出标记会让每颗健康牙都在摘要里显示一次
        $this->assertSame(['21' => 'caries'], $teeth);
        $this->assertSame(1, $summary['tooth_count']);
    }

    // ─── 存量数据回填迁移 ──────────────────────────────────────────

    public function test_backfill_migration_restores_status_from_color(): void
    {
        $base = [
            'tooth'          => 31,
            'tooth_number'   => 31,
            'tooth_type'     => 'permanent',
            'appointment_id' => $this->appointment->id,
            '_who_added'     => $this->admin->id,
            'created_at'     => now(),
            'updated_at'     => now(),
        ];

        // 历史行：建列时被 DEFAULT 'normal' 统一填充，真实状态只在 color 里
        $legacyId = DB::table('dental_charts')->insertGetId(
            $base + ['tooth_status' => 'normal', 'color' => '2']
        );
        // 医生主动标记的行：不应被回填改动
        $markedId = DB::table('dental_charts')->insertGetId(
            array_merge($base, ['tooth' => 32, 'tooth_number' => 32, 'tooth_status' => 'crown', 'color' => '2'])
        );
        // color 折算不出来的行：保持 normal
        $plainId = DB::table('dental_charts')->insertGetId(
            array_merge($base, ['tooth' => 33, 'tooth_number' => 33, 'tooth_status' => 'normal', 'color' => null])
        );

        $migration = require database_path(
            'migrations/2026_08_02_120136_backfill_dental_chart_tooth_status_from_color.php'
        );
        $migration->up();

        $this->assertSame('caries', DB::table('dental_charts')->find($legacyId)->tooth_status);
        $this->assertSame('crown', DB::table('dental_charts')->find($markedId)->tooth_status);
        $this->assertSame('normal', DB::table('dental_charts')->find($plainId)->tooth_status);

        // 幂等：再跑一次不会二次改写
        $migration->up();
        $this->assertSame('caries', DB::table('dental_charts')->find($legacyId)->tooth_status);
    }
}
