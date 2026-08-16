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
use Tests\TestCase;

class MedicalCaseCrudSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();

        $branch    = Branch::create(['name' => 'Main Branch', 'is_active' => true]);
        $adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);

        $this->admin = User::factory()->create([
            'role_id'   => $adminRole->id,
            'branch_id' => $branch->id,
            'password'  => bcrypt('password'),
        ]);

        $permission = Permission::firstOrCreate(
            ['slug' => 'manage-medical-cases'],
            ['name' => 'Manage Medical Cases']
        );
        RolePermission::firstOrCreate([
            'role_id'       => $adminRole->id,
            'permission_id' => $permission->id,
        ]);
    }

    /** @test */
    public function medical_cases_datatable_returns_success(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/medical-cases?draw=1&start=0&length=10', [
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }

    /** @test */
    public function doctor_can_save_a_medical_case_draft_without_create_patient_permission(): void
    {
        $doctorRole = Role::create(['name' => 'Doctor', 'slug' => 'doctor']);

        foreach (['view-medical-cases', 'manage-medical-cases'] as $slug) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => ucfirst(str_replace('-', ' ', $slug))]
            );
            RolePermission::create([
                'role_id'       => $doctorRole->id,
                'permission_id' => $permission->id,
            ]);
        }

        $doctor = User::factory()->create([
            'role_id'   => $doctorRole->id,
            'branch_id' => Branch::firstOrFail()->id,
            'is_doctor' => true,
        ]);
        $patient = Patient::create([
            'patient_no' => 'MC-DRAFT-001',
            'surname'    => '测试',
            'othername'  => '患者',
            'gender'     => 'Male',
            'phone_no'   => '13800138000',
            '_who_added' => $doctor->id,
        ]);

        $this->assertFalse(
            $doctorRole->hasPermission('create-patients'),
            '医生不应为了保存既有患者的病例而被迫获得新建患者权限'
        );

        $response = $this->actingAs($doctor)->postJson('/medical-cases', [
            'patient_id' => $patient->id,
            'case_date'  => now()->toDateString(),
            'is_draft'   => '1',
        ]);

        $response->assertOk()
            ->assertJson([
                'status'  => true,
                'message' => '草稿已保存',
            ]);
        $this->assertDatabaseHas('medical_cases', [
            'patient_id' => $patient->id,
            'doctor_id'  => $doctor->id,
            'is_draft'   => 1,
        ]);
    }

    /**
     * 病历打印取数不能炸。
     *
     * getPrintData() 原先按 VitalSign::where('medical_case_id', ...) 查最近一次
     * 体征，而 vital_signs 表根本没有这一列（体征是按患者/预约记录的）——
     * SQL 直接抛 Unknown column，也就是说 /print-medical-case/{id} 从来没有
     * 成功过一次。页面上看不出来，只有真去打印才发现。
     */
    /** @test */
    public function print_data_can_be_assembled_for_a_medical_case(): void
    {
        $patient = Patient::create([
            'patient_no' => 'MC-PRINT-001',
            'surname'    => '测试',
            'othername'  => '患者',
            'gender'     => 'Male',
            'phone_no'   => '13800138001',
            '_who_added' => $this->admin->id,
        ]);

        $case = \App\MedicalCase::create([
            'case_no'    => 'MC-PRINT-0001',
            'patient_id' => $patient->id,
            'doctor_id'  => $this->admin->id,
            'case_date'  => now()->toDateString(),
            '_who_added' => $this->admin->id,
        ]);

        // 该患者的体征按 patient_id 记录，打印时应取到它
        \App\VitalSign::create([
            'patient_id'  => $patient->id,
            'temperature' => 36.7,
            'heart_rate'  => 72,
            'recorded_at' => now(),
            '_who_added'  => $this->admin->id,
        ]);

        $data = app(\App\Services\MedicalCaseService::class)->getPrintData($case->id);

        $this->assertSame($case->id, $data['case']->id);
        $this->assertNotNull($data['latestVitalSign'], '按 patient_id 应能取到该患者的体征');
        $this->assertEquals(72, $data['latestVitalSign']->heart_rate);
    }

    /**
     * 病历详情页要把录入的内容显示出来。
     *
     * 此前详情页只渲染主诉和现病史，检查/辅助检查/诊断/治疗/医嘱/复诊一概不展示 ——
     * 医生录完回来看，除了这两项什么都看不到，会以为资料没保存上。
     * 数据一直是完整的（打印页渲染了全部字段），缺的只是详情页的展示。
     */
    /** @test */
    public function case_detail_page_shows_what_was_entered(): void
    {
        $patient = Patient::create([
            'patient_no' => 'MC-SHOW-001',
            'surname'    => '展示',
            'othername'  => '患者',
            'gender'     => 'Male',
            'phone_no'   => '13800138002',
            '_who_added' => $this->admin->id,
        ]);

        $case = \App\MedicalCase::create([
            'case_no'               => 'MC-SHOW-0001',
            'patient_id'            => $patient->id,
            'doctor_id'             => $this->admin->id,
            'case_date'             => now()->toDateString(),
            'chief_complaint'       => '右下后牙冷热痛',
            'examination'           => '46 叩痛阳性',
            'examination_teeth'     => ['46'],
            'auxiliary_examination' => '根尖片示根周膜增宽',
            'diagnosis'             => '慢性根尖周炎',
            'diagnosis_code'        => 'K04.5',
            'related_teeth'         => ['46'],
            'treatment'             => '根管治疗',
            'medical_orders'        => '避免patient患侧咀嚼',
            'next_visit_date'       => now()->addWeek()->toDateString(),
            'next_visit_note'       => '复诊换药',
            '_who_added'            => $this->admin->id,
        ]);

        $html = $this->actingAs($this->admin)
            ->get('/medical-cases/' . $case->id)
            ->assertOk()
            ->getContent();

        foreach ([
            '46 叩痛阳性',
            '根尖片示根周膜增宽',
            '慢性根尖周炎',
            'K04.5',
            '根管治疗',
            '复诊换药',
        ] as $entered) {
            $this->assertStringContainsString(
                $entered,
                $html,
                "录入的「{$entered}」应当在病历详情页上显示出来"
            );
        }

        // case_date 有 date:Y-m-d cast，但那个格式只作用于 toArray()；
        // Blade 的 {{ }} 走 Carbon 的 __toString()，会输出 2025-12-14 00:00:00。
        $this->assertStringNotContainsString(
            $case->case_date->format('Y-m-d') . ' 00:00:00',
            $html,
            '就诊日期不该带零点时分秒'
        );
    }

    /**
     * 选完患者要能看到这个人的病历历史。
     *
     * 走通用「新建病历」入口时患者是后选的，而侧栏历史是服务端渲染的：
     * getCreateData() 根本没查 historyRecords，edit.blade 用的是 $historyRecords ?? []，
     * 于是那一栏永远停在「暂无历史记录」—— 医生看不到这个人以前看过什么，
     * 而那恰恰是写病历时最需要参考的一栏。
     */
    /** @test */
    public function case_history_can_be_loaded_for_a_patient(): void
    {
        $patient = Patient::create([
            'patient_no' => 'MC-HIST-001',
            'surname'    => '历史',
            'othername'  => '患者',
            'gender'     => 'Male',
            'phone_no'   => '13800138003',
            '_who_added' => $this->admin->id,
        ]);

        \App\MedicalCase::create([
            'case_no'         => 'MC-HIST-0001',
            'patient_id'      => $patient->id,
            'doctor_id'       => $this->admin->id,
            'case_date'       => now()->subMonth()->toDateString(),
            'title'           => '上次就诊',
            'chief_complaint' => '左下牙隐痛',
            'diagnosis'       => '牙隐裂',
            '_who_added'      => $this->admin->id,
        ]);

        $html = $this->actingAs($this->admin)
            ->get('/medical-case-history/' . $patient->id)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('上次就诊', $html);
        $this->assertStringContainsString('左下牙隐痛', $html);
        $this->assertStringNotContainsString(
            __('medical_cases.no_history_records'),
            $html,
            '这个患者有历史病历，不该显示「暂无历史记录」'
        );
    }

    /** @test */
    public function case_history_is_empty_for_a_patient_without_cases(): void
    {
        $patient = Patient::create([
            'patient_no' => 'MC-HIST-002',
            'surname'    => '新',
            'othername'  => '患者',
            'gender'     => 'Female',
            'phone_no'   => '13800138004',
            '_who_added' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get('/medical-case-history/' . $patient->id)
            ->assertOk()
            ->assertSee(__('medical_cases.no_history_records'));
    }
}
