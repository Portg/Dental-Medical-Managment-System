<?php

namespace Tests\Feature;

use App\Branch;
use App\MedicalCase;
use App\Patient;
use App\Permission;
use App\Role;
use App\RolePermission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 病历纸：编辑页本身就是那张 A4 纸，编辑完直接打印。
 *
 * 改之前这一页是后台表单的样子（一摞带灰色标题条的卡片 + 右侧工具栏），而打印走
 * 的是另一份模板（medical_cases/print.blade.php 的 DomPDF 表格版式）。医生编辑时
 * 看到的和打出来的不是一套东西，而且编辑页顶上**没有打印按钮** —— 要先保存、
 * 再跳到病历详情页才找得到入口。
 *
 * 这组用例钉的是「纸」这件事在服务端能验证的部分：纸在不在、打印入口在不在、
 * 版式样式表有没有加载、病历内容有没有真的落在纸上，以及页面上没有重复 id。
 * 横格线、页边距那些纯视觉的部分只能靠浏览器看，不在这里断言。
 */
class MedicalRecordPaperTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;
    private Patient $patient;

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
    }

    private function newRecordPage(): string
    {
        return $this->actingAs($this->doctor)
            ->get('/medical-case-new/' . $this->patient->id)
            ->assertOk()
            ->getContent();
    }

    public function test_新建病历页渲染的是一张病历纸(): void
    {
        $html = $this->newRecordPage();

        $this->assertStringContainsString('class="mr-paper"', $html, '页面上应当有一张纸');
        $this->assertStringContainsString('css/medical-record-paper.css', $html, '纸的版式样式表要加载');
        $this->assertStringContainsString('include_js/medical_record_paper.js', $html, '文本域自动增高要加载');
    }

    /**
     * 打印入口必须在编辑页上。原来只有「保存草稿」「提交病历」两个按钮，
     * 医生写完了在这一页找不到打印。
     */
    public function test_编辑页上就有打印按钮(): void
    {
        $html = $this->newRecordPage();

        $this->assertStringContainsString('printMedicalRecord()', $html, '编辑页要有打印入口');
        $this->assertStringContainsString('id="btn-print-record"', $html);
    }

    /**
     * 纸上要有抬头（诊所名 + 病历记录）和落款（医师签名）—— 这是一张病历该有的
     * 样子。原来编辑页两样都没有，它们只存在于那份独立的打印模板里。
     */
    public function test_纸上有抬头与落款(): void
    {
        $html = $this->newRecordPage();

        $this->assertStringContainsString(__('medical_cases.medical_record'), $html, '抬头');
        $this->assertStringContainsString('mr-sheet-title', $html);
        $this->assertStringContainsString('mr-sign', $html, '落款');
        $this->assertStringContainsString(__('medical_cases.doctor_signature'), $html);
    }

    /**
     * 患者身份与过敏史印在纸上，不只是侧栏的界面提示 —— 接诊时必须看得见，
     * 打出来的病历上也必须有。
     */
    public function test_患者与过敏史落在纸上(): void
    {
        $html = $this->newRecordPage();

        $paper = $this->slicePaper($html);

        $this->assertStringContainsString('刘万友', $paper, '姓名要在纸上');
        $this->assertStringContainsString('13800138014', $paper, '电话要在纸上');
        $this->assertStringContainsString('青霉素', $paper, '过敏史要在纸上');
    }

    /**
     * 已有病历打开时，写过的内容要落在纸面对应的位置上。
     */
    public function test_已有病历的内容落在纸上(): void
    {
        $case = MedicalCase::create([
            'case_no'         => 'MC-PAPER-1',
            'patient_id'      => $this->patient->id,
            'doctor_id'       => $this->doctor->id,
            'case_date'       => now()->format('Y-m-d'),
            'visit_type'      => 'initial',
            'chief_complaint' => '右上后牙痛3天',
            'status'          => 'Open',
            '_who_added'      => $this->doctor->id,
        ]);

        $html = $this->actingAs($this->doctor)
            ->get('/medical-cases/' . $case->id . '/edit')
            ->assertOk()
            ->getContent();

        $paper = $this->slicePaper($html);

        $this->assertStringContainsString('右上后牙痛3天', $paper, '主诉要在纸上');
        $this->assertStringContainsString('MC-PAPER-1', $paper, '病历编号要在纸上');
    }

    /**
     * 同一个 id 在页面上只能出现一次。
     *
     * 姓名/性别年龄/过敏史这几块是从侧栏搬到纸上的，而 enableFormWithPatient()
     * 用 $('#patient-name') 这类选择器往里写 —— 两处同 id 的话 jQuery 只认得到
     * 第一个，另一处永远是空的。这套代码为重复 id 栽过一次（见 46287dc：
     * DOM id 重复导致保存点不动），所以钉成用例。
     */
    public function test_页面上没有重复的id(): void
    {
        $html = $this->newRecordPage();

        preg_match_all('/\bid="([^"]+)"/', $html, $matches);

        $duplicates = array_filter(
            array_count_values($matches[1]),
            fn ($count) => $count > 1
        );

        $this->assertSame(
            [],
            $duplicates,
            '这些 id 在页面上出现了不止一次：' . json_encode(array_keys($duplicates), JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * 取 .mr-paper 那一段，避免断言命中侧栏或弹窗里的同名文字。
     */
    private function slicePaper(string $html): string
    {
        $start = strpos($html, 'class="mr-paper"');
        $this->assertNotFalse($start, '页面上没有找到病历纸');

        $end = strpos($html, 'class="mr-tools"', $start);

        return $end === false
            ? substr($html, $start)
            : substr($html, $start, $end - $start);
    }
}
