<?php

namespace Tests\Feature;

use App\Appointment;
use App\Branch;
use App\Patient;
use App\Role;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 复诊率 / 患者来源报表导出。
 *
 * 两个 export() 原先都只有 TODO 注释、且未挂路由，点「导出」什么也不会发生。
 * 这里验证：路由可达、返回真正的 xlsx、且流失患者表遵守 AG-045
 * （不含 NIN，手机号脱敏）。
 */
class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Test Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);

        $this->admin = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $branch->id,
            'status'    => User::STATUS_ACTIVE,
        ]);

        // 造一个「流失患者」：最后一次就诊在 120 天前
        $patient = Patient::create([
            'patient_no' => 'P20260801',
            'surname'    => '王',
            'othername'  => '五',
            'gender'     => 'Male',
            'phone_no'   => '13812345678',
            'nin'        => '110101199001011234',
            '_who_added' => $this->admin->id,
        ]);

        Appointment::create([
            'start_date'        => now()->subDays(120)->format('Y-m-d'),
            'end_date'          => now()->subDays(120)->format('Y-m-d'),
            'start_time'        => '10:00 AM',
            'visit_information' => 'appointment',
            'patient_id'        => $patient->id,
            'doctor_id'         => $this->admin->id,
            'branch_id'         => $branch->id,
            'status'            => Appointment::STATUS_COMPLETED,
            '_who_added'        => $this->admin->id,
            'sort_by'           => now()->subDays(120)->format('Y-m-d') . ' 10:00:00',
        ]);
    }

    private function assertIsXlsx($response): void
    {
        $response->assertStatus(200);

        // maatwebsite/excel 的 download() 返回 BinaryFileResponse（非流式），
        // 但不同版本/驱动也可能给出流式响应，两种都要能取到内容。
        $baseResponse = $response->baseResponse;
        if ($baseResponse instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            $content = file_get_contents($baseResponse->getFile()->getPathname());
        } else {
            $content = $response->streamedContent();
        }

        $this->assertNotEmpty($content, '导出内容为空');
        // xlsx 本质是 zip，魔数为 PK\x03\x04
        $this->assertSame("PK\x03\x04", substr($content, 0, 4), '返回的不是有效的 xlsx 文件');
    }

    public function test_revisit_rate_export_returns_xlsx(): void
    {
        $response = $this->actingAs($this->admin)->get('/export-revisit-rate?' . http_build_query([
            'start_date' => now()->subMonth()->format('Y-m-d'),
            'end_date'   => now()->format('Y-m-d'),
        ]));

        $this->assertIsXlsx($response);
    }

    public function test_patient_source_export_returns_xlsx(): void
    {
        $response = $this->actingAs($this->admin)->get('/export-patient-source?' . http_build_query([
            'start_date' => now()->subMonth()->format('Y-m-d'),
            'end_date'   => now()->format('Y-m-d'),
        ]));

        $this->assertIsXlsx($response);
    }

    public function test_exports_work_without_explicit_date_range(): void
    {
        // 控制器需自带默认区间，否则从菜单直接点导出会因缺参而炸
        $this->assertIsXlsx($this->actingAs($this->admin)->get('/export-revisit-rate'));
        $this->assertIsXlsx($this->actingAs($this->admin)->get('/export-patient-source'));
    }

    public function test_lost_patient_sheet_masks_phone_and_omits_nin(): void
    {
        // AG-045：报表导出不得包含 NIN，手机号必须脱敏为 138****1234
        $data = app(\App\Services\RevisitRateReportService::class)
            ->getReportData(now()->subMonth()->format('Y-m-d'), now()->format('Y-m-d'));

        $export = new \App\Exports\RevisitRateExport(
            $data,
            now()->subMonth()->format('Y-m-d'),
            now()->format('Y-m-d')
        );

        $sheets = $export->sheets();
        $lostSheet = end($sheets);
        $rows = $lostSheet->array();

        $this->assertNotEmpty($rows, '应至少包含一条流失患者记录');

        $flat = json_encode($rows, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('110101199001011234', $flat, '导出不得包含 NIN');
        $this->assertStringNotContainsString('13812345678', $flat, '手机号必须脱敏');
        $this->assertStringContainsString('138****5678', $flat, '手机号应脱敏为 138****5678 格式');
    }

    public function test_export_requires_view_reports_permission(): void
    {
        $role = Role::create(['name' => 'Nurse', 'slug' => 'nurse']);
        $nurse = User::factory()->create([
            'role_id'   => $role->id,
            'branch_id' => $this->admin->branch_id,
            'status'    => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($nurse)->get('/export-revisit-rate')->assertStatus(403);
    }
}
