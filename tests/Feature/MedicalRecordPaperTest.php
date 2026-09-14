<?php

namespace Tests\Feature;

use App\Branch;
use App\Diagnosis;
use App\MedicalCase;
use App\MedicalCaseItem;
use App\Patient;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 病历的两个形态：表单负责录入，病历纸负责呈现。
 *
 * 参考的那套桌面软件就是这么分的 —— 编辑时是一屏密排的字段表单（下拉、输入框、
 * 段落标题条），而交到患者、同行或病案室手里的是一份文书。两者不混：
 * 一个字段一个输入框的形态是给录入用的，不是给人读的。
 *
 * 这组用例钉三件事：
 *   1. 编辑页是表单，不是纸（纸上不该有输入控件，反过来表单也不该长成纸）
 *   2. 编辑页有打印入口，指向那张纸 —— 原来这一页压根没有打印按钮，
 *      医生写完得先保存、再跳到病历详情页才找得到
 *   3. 纸上有的是内容，不是控件
 */
class MedicalRecordPaperTest extends TestCase
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

        // 病历页按 manage-medical-cases 判权限，不发这条进不去（403）
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

        $this->patient = Patient::create([
            'patient_no' => '20260914', 'surname' => '刘', 'othername' => '万友',
            'gender' => 'Male', 'phone_no' => '13800138014',
            'drug_allergies_other' => '青霉素',
            '_who_added' => $this->doctor->id,
        ]);

        $this->case = MedicalCase::create([
            'case_no'         => 'MC-SHEET-1',
            'patient_id'      => $this->patient->id,
            'doctor_id'       => $this->doctor->id,
            'case_date'       => now()->format('Y-m-d'),
            'visit_type'      => 'initial',
            'chief_complaint' => '右上后牙痛3天',
            'medical_orders'  => '勿用患侧咀嚼',
            'status'          => 'Open',
            '_who_added'      => $this->doctor->id,
        ]);

        MedicalCaseItem::create([
            'medical_case_id' => $this->case->id, 'section' => 'examination',
            'tooth_no' => '16', 'content' => '远中邻面深龋，探及穿髓孔',
            'sort_order' => 0, '_who_added' => $this->doctor->id,
        ]);
        MedicalCaseItem::create([
            'medical_case_id' => $this->case->id, 'section' => 'treatment',
            'tooth_no' => '16', 'content' => '开髓引流，封失活剂',
            'sort_order' => 0, '_who_added' => $this->doctor->id,
        ]);
        Diagnosis::create([
            'medical_case_id' => $this->case->id, 'patient_id' => $this->patient->id,
            'diagnosis_name' => '急性牙髓炎', 'tooth_no' => '16', 'icd_code' => 'K04.0',
            'diagnosis_date' => now()->format('Y-m-d'), 'status' => 'Active',
            '_who_added' => $this->doctor->id,
        ]);
    }

    private function sheet(): string
    {
        return $this->actingAs($this->doctor)
            ->get('/print-medical-case/' . $this->case->id)
            ->assertOk()
            ->getContent();
    }

    private function editForm(): string
    {
        return $this->actingAs($this->doctor)
            ->get('/medical-cases/' . $this->case->id . '/edit')
            ->assertOk()
            ->getContent();
    }

    // ─── 录入态：表单 ────────────────────────────────────────────

    /**
     * 编辑页是录入表单。纸的那套版式（抬头、A4 纸面）不该出现在这里 ——
     * 曾经把编辑页整个做成一张可编辑的纸，结果是纸上塞满了输入控件，
     * 而表单反倒没了存在意义。
     */
    public function test_编辑页是录入表单不是病历纸(): void
    {
        $html = $this->editForm();

        $this->assertStringContainsString('id="medical-record-form"', $html, '编辑页要有录入表单');
        $this->assertStringContainsString('soap-section', $html, '录入分段');
        $this->assertStringNotContainsString('class="mr-paper"', $html, '编辑页不该是一张纸');
        $this->assertStringNotContainsString('mr-sheet-title', $html, '编辑页不该有病历纸的抬头');
    }

    /**
     * 打印入口必须在编辑页上，且指向那张纸。
     */
    public function test_编辑页的打印入口指向病历纸(): void
    {
        $html = $this->editForm();

        $this->assertStringContainsString('id="btn-print-record"', $html, '编辑页要有打印入口');
        $this->assertStringContainsString('print-medical-case/' . $this->case->id, $html, '打印要指向病历纸');
    }

    // ─── 呈现态：病历纸 ──────────────────────────────────────────

    public function test_病历纸渲染的是一张纸(): void
    {
        $html = $this->sheet();

        $this->assertStringContainsString('class="mr-paper"', $html);
        $this->assertStringContainsString('css/medical-record-paper.css', $html);
        $this->assertStringContainsString(__('medical_cases.medical_record'), $html, '抬头');
        $this->assertStringContainsString(__('medical_cases.doctor_signature'), $html, '落款');
    }

    /**
     * 纸是呈现态，上面只该有内容。留一个输入框在病历上，那就不是文书了。
     */
    public function test_纸上没有任何输入控件(): void
    {
        $paper = $this->slicePaper($this->sheet());

        foreach (['<input', '<textarea', '<select'] as $tag) {
            $this->assertStringNotContainsString($tag, $paper, "病历纸上不该出现 {$tag}");
        }
    }

    /**
     * 病历内容要真的落在纸上 —— 包括分行明细的牙位与文字、带 ICD 的诊断。
     * 只印 medical_cases 上那几列派生文本的话，牙位和文字会重新揉成一段话。
     */
    public function test_病历内容与分行明细落在纸上(): void
    {
        $paper = $this->slicePaper($this->sheet());

        $this->assertStringContainsString('刘万友', $paper, '患者');
        $this->assertStringContainsString('青霉素', $paper, '过敏史');
        $this->assertStringContainsString('MC-SHEET-1', $paper, '病历号');
        $this->assertStringContainsString('右上后牙痛3天', $paper, '主诉');
        $this->assertStringContainsString('远中邻面深龋，探及穿髓孔', $paper, '检查');
        $this->assertStringContainsString('开髓引流，封失活剂', $paper, '治疗');
        $this->assertStringContainsString('急性牙髓炎', $paper, '诊断');
        $this->assertStringContainsString('K04.0', $paper, 'ICD 编码');
        $this->assertStringContainsString('勿用患侧咀嚼', $paper, '医嘱');
        $this->assertStringContainsString('16', $paper, '牙位');
    }

    /**
     * 老病历（2026-08-25 把诊断接进 diagnoses 表之前建的）诊断只存在
     * medical_cases.diagnosis 那段派生文本里。检查与治疗因为
     * getCaseItemsForEdit 自带回退印得出来，诊断若不兜这一下就是空的 ——
     * 一份病历独缺一段，比整份印不出来更容易被漏掉。
     */
    public function test_老病历的诊断从派生文本兜底(): void
    {
        $legacy = MedicalCase::create([
            'case_no'    => 'MC-LEGACY-1',
            'patient_id' => $this->patient->id,
            'doctor_id'  => $this->doctor->id,
            'case_date'  => now()->format('Y-m-d'),
            'visit_type' => 'initial',
            'chief_complaint' => '左上乳牙双排牙',
            // 只有派生文本，没有 diagnoses 记录 —— 老病历就是这个样子
            'examination' => '55松动',
            'diagnosis'   => '55乳牙滞留',
            'treatment'   => '55局麻下拔除患牙',
            'status'      => 'Open',
            '_who_added'  => $this->doctor->id,
        ]);

        $html = $this->actingAs($this->doctor)
            ->get('/print-medical-case/' . $legacy->id)->assertOk()->getContent();
        $paper = $this->slicePaper($html);

        $this->assertStringContainsString('55乳牙滞留', $paper, '老病历的诊断不该丢');
        $this->assertStringContainsString('55松动', $paper, '检查');
        $this->assertStringContainsString('55局麻下拔除患牙', $paper, '治疗');
    }

    /**
     * 归档 PDF 仍走 DomPDF 的 print.blade.php，与这张纸分开维护 ——
     * DomPDF 不支持 flex / grid，共用不了这套版式。这条钉的是那条路没被改坏。
     */
    public function test_归档pdf仍然走得通(): void
    {
        $response = $this->actingAs($this->doctor)
            ->get('/medical-cases/' . $this->case->id . '/export-pdf');

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    /**
     * 页面上同一个 id 只能出现一次（这套代码为重复 id 栽过一次，见 46287dc）。
     */
    public function test_病历纸上没有重复的id(): void
    {
        preg_match_all('/\bid="([^"]+)"/', $this->sheet(), $matches);

        $duplicates = array_filter(
            array_count_values($matches[1]),
            fn ($count) => $count > 1
        );

        $this->assertSame(
            [],
            $duplicates,
            '这些 id 出现了不止一次：' . json_encode(array_keys($duplicates), JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * 取 .mr-paper 那一段，避免断言命中工具条或布局里的同名文字。
     */
    private function slicePaper(string $html): string
    {
        $start = strpos($html, 'class="mr-paper"');
        $this->assertNotFalse($start, '页面上没有找到病历纸');

        return substr($html, $start);
    }
}
