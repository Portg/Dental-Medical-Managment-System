<?php

namespace Tests\Feature;

use App\Branch;
use App\Diagnosis;
use App\MedicalCase;
use App\Patient;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\Services\MedicalCaseService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * 诊断的 ICD 名称要随病历落库。
 *
 * 原来 diagnoses 只存 icd_code。医生在下拉里选的是「K04.0 - 牙髓炎」，落库只落了
 * K04.0，名称当场丢掉，于是两处都退化成裸编码：
 *
 *   - 编辑页：getDiagnosesForEdit 不给名称，JS 的 escapeHtml(icdText || icd) 退回
 *     成 K04.0，重新打开病历医生没法确认当初选的是哪一条
 *   - 病历纸：一个裸编码缀在诊断名后面
 *
 * 名称在服务端按编码解析、随行落库，而不是渲染时查表 —— 病历是医疗文书，写下时
 * 是什么就该永远是什么。码表以后增删条目、或者 odontogram 的翻译改了字，历史病历
 * 的诊断含义都不该跟着变。
 */
class DiagnosisIcdNameTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;
    private Patient $patient;
    private MedicalCase $case;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $role   = Role::create(['name' => 'Doctor', 'slug' => 'doctor']);

        foreach (['view-medical-cases', 'manage-medical-cases'] as $slug) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $slug, 'module' => '病历管理']
            );
            RolePermission::create(['role_id' => $role->id, 'permission_id' => $permission->id]);
        }

        $this->doctor = User::factory()->create([
            'role_id' => $role->id, 'branch_id' => $branch->id,
            'status' => User::STATUS_ACTIVE, 'is_doctor' => true,
        ]);
        Auth::login($this->doctor);

        $this->patient = Patient::create([
            'patient_no' => 'ICD-1', 'surname' => '刘', 'othername' => '万友',
            'gender' => 'Male', 'phone_no' => '13800138031', '_who_added' => $this->doctor->id,
        ]);

        $this->case = MedicalCase::create([
            'case_no' => 'MC-ICD-1', 'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id, 'case_date' => now()->format('Y-m-d'),
            'visit_type' => 'initial', 'chief_complaint' => '右上后牙痛',
            'status' => 'Open', '_who_added' => $this->doctor->id,
        ]);
    }

    private function service(): MedicalCaseService
    {
        return app(MedicalCaseService::class);
    }

    public function test_保存诊断时名称随编码落库(): void
    {
        $rows = $this->service()->normalizeDiagnoses([
            ['tooth_no' => '16', 'content' => '急性牙髓炎', 'icd_code' => 'K04.0'],
        ]);

        $this->service()->syncDiagnoses($this->case, $rows['rows']);

        $this->assertDatabaseHas('diagnoses', [
            'medical_case_id' => $this->case->id,
            'icd_code'        => 'K04.0',
            'icd_name'        => __('odontogram.pulpitis'),
        ]);
    }

    /**
     * 名称由服务端按编码解析，不收前端提交的那一份 —— label 是派生数据，
     * 前端传什么都不该影响落库的内容。
     */
    public function test_名称不听前端的(): void
    {
        $rows = $this->service()->normalizeDiagnoses([
            ['tooth_no' => '16', 'content' => '急性牙髓炎', 'icd_code' => 'K04.0',
             'icd_name' => '前端瞎传的名字', 'icd_text' => 'K04.0 - 也是瞎传的'],
        ]);

        $this->service()->syncDiagnoses($this->case, $rows['rows']);

        $this->assertDatabaseHas('diagnoses', [
            'medical_case_id' => $this->case->id,
            'icd_name'        => __('odontogram.pulpitis'),
        ]);
        $this->assertDatabaseMissing('diagnoses', [
            'medical_case_id' => $this->case->id,
            'icd_name'        => '前端瞎传的名字',
        ]);
    }

    /**
     * 码表里没有的编码宁可留空，也不要编一个名字出来。
     */
    public function test_码表里没有的编码不编名字(): void
    {
        $rows = $this->service()->normalizeDiagnoses([
            ['tooth_no' => null, 'content' => '某某病', 'icd_code' => 'Z99.9'],
        ]);

        $this->service()->syncDiagnoses($this->case, $rows['rows']);

        $this->assertDatabaseHas('diagnoses', [
            'medical_case_id' => $this->case->id,
            'icd_code'        => 'Z99.9',
            'icd_name'        => null,
        ]);
    }

    /**
     * 编辑页回填要给出「K04.0 - 牙髓炎」整行，不是裸编码 ——
     * 这正是 JS 里 escapeHtml(icdText || icd) 读的那个值。
     */
    public function test_编辑页回填带出编码与名称(): void
    {
        $rows = $this->service()->normalizeDiagnoses([
            ['tooth_no' => '16', 'content' => '急性牙髓炎', 'icd_code' => 'K04.0'],
        ]);
        $this->service()->syncDiagnoses($this->case, $rows['rows']);

        $forEdit = $this->service()->getDiagnosesForEdit($this->case->fresh());

        $this->assertCount(1, $forEdit);
        $this->assertSame('K04.0', $forEdit[0]['icd_code']);
        $this->assertSame('K04.0 - ' . __('odontogram.pulpitis'), $forEdit[0]['icd_text']);
    }

    /**
     * 存量记录（迁移前落的，只有编码没有名称）回填时按码表补一次，
     * 不至于让老病历在编辑页上仍旧只剩裸编码。
     */
    public function test_老记录没存名称时按码表补(): void
    {
        Diagnosis::create([
            'medical_case_id' => $this->case->id, 'patient_id' => $this->patient->id,
            'diagnosis_name' => '急性牙髓炎', 'tooth_no' => '16',
            'icd_code' => 'K04.0', 'icd_name' => null,      // 迁移前的样子
            'diagnosis_date' => now()->format('Y-m-d'), 'status' => 'Active',
            '_who_added' => $this->doctor->id,
        ]);

        $forEdit = $this->service()->getDiagnosesForEdit($this->case->fresh());

        $this->assertSame('K04.0 - ' . __('odontogram.pulpitis'), $forEdit[0]['icd_text']);
    }

    /**
     * 病历纸上：诊断行只有临床叙述，编码另起一行。
     *
     * ICD 是给医保结算与病案统计用的编码，不是给人读的叙述。缀在诊断名后面既没帮到
     * 懂的人（他要的是能导出结算的结构化数据），又干扰了不懂的人。
     */
    public function test_病历纸上编码不混进诊断行(): void
    {
        $rows = $this->service()->normalizeDiagnoses([
            ['tooth_no' => '16', 'content' => '急性牙髓炎', 'icd_code' => 'K04.0'],
        ]);
        $this->service()->syncDiagnoses($this->case, $rows['rows']);

        $html = $this->actingAs($this->doctor)
            ->get('/print-medical-case/' . $this->case->id)->assertOk()->getContent();

        $start = strpos($html, 'class="mr-paper"');
        $paper = substr($html, $start);

        // 诊断行：有诊断名，没有编码
        preg_match('/<div class="mr-item">.*?急性牙髓炎.*?<\/div>/s', $paper, $m);
        $this->assertNotEmpty($m, '纸上应当有诊断行');
        $this->assertStringNotContainsString('K04.0', $m[0], '编码不该混进诊断行');

        // 编码单独成行，且编码与名称都在 —— 只印编码看不懂，只印名称对不上医保
        $this->assertStringContainsString('mr-coding', $paper, '编码应当另起一行');
        $this->assertStringContainsString('K04.0', $paper);
        $this->assertStringContainsString(__('odontogram.pulpitis'), $paper);
    }

    /**
     * 没有编码的诊断不该在纸上凭空多出一行空的「ICD编码」。
     */
    public function test_没有编码时不印编码行(): void
    {
        $rows = $this->service()->normalizeDiagnoses([
            ['tooth_no' => '16', 'content' => '牙龈炎', 'icd_code' => ''],
        ]);
        $this->service()->syncDiagnoses($this->case, $rows['rows']);

        $html = $this->actingAs($this->doctor)
            ->get('/print-medical-case/' . $this->case->id)->assertOk()->getContent();

        $this->assertStringNotContainsString('mr-coding', $html);
    }
}
